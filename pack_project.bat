$Out = "project_files.zip"
$Files = @(
  "app/Services/CategoryFilterService.php",
  "app/Services/CatalogSessionManager.php",
  "app/Livewire/CatlogTreeLevel1.php",
  "app/Livewire/CatlogTreeLevel2.php",
  "app/Livewire/CatlogTreeLevel3.php",
  "app/Livewire/Catlogs.php",
  "app/Livewire/Illustrations.php",
  "app/Livewire/VehicleSearchBox.php",
  "app/Livewire/SearchBoxvin.php",
  "resources/views/livewire/vehicle-search-box.blade.php",
  "resources/views/livewire/callout-viewer-modal.blade.php",
  "resources/views/livewire/alternative.blade.php",
  "app/Http/Controllers/Front/CartController.php",
  "app/Models/Cart.php",
  "app/Models/Product.php",
  "app/Models/MerchantProduct.php",
  "app/Helpers/helper.php",
  "app/Helpers/PriceHelper.php",
  "app/Providers/AppServiceProvider.php",
  "routes/web.php"
)

$Temp = New-Item -ItemType Directory -Path (Join-Path $env:TEMP ([System.Guid]::NewGuid().ToString()))
Write-Host "Collecting files..."
foreach ($f in $Files) {
  if (Test-Path $f) {
    $dst = Join-Path $Temp.FullName (Split-Path $f -Parent)
    New-Item -ItemType Directory -Path $dst -Force | Out-Null
    Copy-Item $f -Destination (Join-Path $dst (Split-Path $f -Leaf)) -Force
    Write-Host "  + $f"
  } else {
    Write-Host "  - missing: $f"
  }
}

Write-Host "Creating archive: $Out"
Compress-Archive -Path (Join-Path $Temp.FullName '*') -DestinationPath $Out -Force

Remove-Item -Recurse -Force $Temp
Write-Host "Done. Archive created at: $(Resolve-Path $Out)"
