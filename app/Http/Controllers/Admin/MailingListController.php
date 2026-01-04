<?php

namespace App\Http\Controllers\Admin;

use App\Models\MailingList;

use Datatables;


class MailingListController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = MailingList::oldest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('sl', function(MailingList $data) {
                                $id = 1;
                                return $id++;
                            })
                            ->toJson();//--- Returning Json Data To Client Side
    }

    public function index(){
        return view('admin.mailing-list.index');
    }

    //*** GET Request
    public function download()
    {
        //  Code for generating csv file
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mailing-list.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Mailing List Emails'));
        $result = MailingList::all();
        foreach ($result as $row){
            fputcsv($output, $row->toArray());
        }
        fclose($output);
    }
}
