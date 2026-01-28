<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use App\Domain\Merchant\Services\MerchantItemImageService;
use App\Domain\Merchant\Models\MerchantPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * MerchantItemImageController - Unified image management
 *
 * Handles photo operations for both merchants and operators.
 */
class MerchantItemImageController extends Controller
{
    public function __construct(
        private MerchantItemQuery $itemQuery,
        private MerchantItemImageService $imageService,
    ) {}

    /**
     * Show photos page
     */
    public function index($itemId)
    {
        $item = $this->authorizeAndGetItem($itemId);

        $photos = $this->imageService->getAllPhotosUrls($item);

        return view('shared.merchant-item-photos', compact('item', 'photos'));
    }

    /**
     * Upload photo
     */
    public function store(Request $request, $itemId)
    {
        $item = $this->authorizeAndGetItem($itemId);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        if (!$this->imageService->validateImage($request->file('photo'))) {
            return redirect()->back()->with('error', __('Invalid image file'));
        }

        $this->imageService->uploadPhoto($item, $request->file('photo'));

        return redirect()->back()->with('success', __('Photo uploaded successfully'));
    }

    /**
     * Set primary photo
     */
    public function setPrimary($itemId, $photoId)
    {
        $item = $this->authorizeAndGetItem($itemId);

        $photo = MerchantPhoto::where('merchant_item_id', $item->id)
            ->where('id', $photoId)
            ->firstOrFail();

        $this->imageService->setPrimaryPhoto($item, $photoId);

        return redirect()->back()->with('success', __('Primary photo updated'));
    }

    /**
     * Delete photo
     */
    public function destroy($itemId, $photoId)
    {
        $item = $this->authorizeAndGetItem($itemId);

        $photo = MerchantPhoto::where('merchant_item_id', $item->id)
            ->where('id', $photoId)
            ->firstOrFail();

        $this->imageService->deletePhoto($photo);

        return redirect()->back()->with('success', __('Photo deleted successfully'));
    }

    /**
     * Authorize and get item based on user role
     */
    private function authorizeAndGetItem($itemId)
    {
        $user = Auth::user();

        // Operator can access any item
        if ($user->role === 'operator' || $user->is_operator) {
            return $this->itemQuery::make()
                ->withRelations()
                ->getQuery()
                ->findOrFail($itemId);
        }

        // Merchant can only access their own items
        if ($user->is_merchant) {
            return $this->itemQuery::make()
                ->forMerchant($user->id)
                ->withRelations()
                ->getQuery()
                ->findOrFail($itemId);
        }

        abort(403, 'Unauthorized');
    }
}
