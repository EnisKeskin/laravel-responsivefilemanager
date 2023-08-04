<?php

namespace Kwaadpepper\Http\Controllers;

use App\Http\Controllers\Controller;
use Kwaadpepper\ResponsiveFileManager\RFM;

class ForceDownloadController extends Controller
{
    public function download()
    {
        if (!session()->exists('RF') || session('RF.verify') != "RESPONSIVEfilemanager") {
            RFM::response(__('forbidden') . RFM::addErrorLocation(), 403)->send();
            exit;
        }

        if (!RFM::checkRelativePath(request()->post('path')) || strpos(request()->post('path'), '/') === 0) {
            RFM::response(__('wrong path') . RFM::addErrorLocation(), 400)->send();
            exit;
        }

        if (strpos(request()->post('name'), '/') !== false) {
            RFM::response(__('wrong path') . RFM::addErrorLocation(), 400)->send();
            exit;
        }
        
        $post = request()->post();

        if (array_key_exists('temp_upload_dir', $post) && !blank($post['temp_upload_dir'])) {
            $slashTrimmedTempUploadDir = trim($post['temp_upload_dir'], '/');
            config(['rfm.upload_dir' => '/'.$slashTrimmedTempUploadDir.'/']);
            config(['rfm.ftp_thumbs_dir' => config('rfm.ftp_thumbs_base_dir').$slashTrimmedTempUploadDir.'/']);
        }

        $ftp = RFM::ftpCon(config('rfm'));

        if ($ftp) {
            $path = config('rfm.base_url') .  config('rfm.upload_dir') . request()->post('path');
        } else {
            $path = config('rfm.current_path') . request()->post('path');
        }

        $name = request()->post('name');
        $info = pathinfo($name);

        if (!RFM::checkExtension($info['extension'], config('rfm'))) {
            RFM::response(__('wrong extension') . RFM::addErrorLocation(), 400)->send();
            exit;
        }

        $file_name = $info['basename'];
        $file_ext = $info['extension'];
        $file_path = $path . $name;

        $local_file_path_to_download = "";
        // make sure the file exists
        //dd($ftp, $file_path, $file_name.'.'.$file_ext, $local_file_path_to_download, RFM::ftpDownloadFile($ftp, $file_path, $file_name.'.'.$file_ext, $local_file_path_to_download));
        if ($ftp) {
            $tempImage = tempnam(sys_get_temp_dir(), $file_name);
            copy($file_path, $tempImage);

            return response()->download($tempImage, $file_name, [
                "Content-Type" => "application/octet-stream",
                "Connection" => "keep-alive",
                "Content-Transfer-Encoding" => "Binary",
                "Cache-Control" => "no-store, no-cache, must-revalidate",
                "Content-disposition" => "attachment; filename=\"" . $file_path . "\"",
            ]);

        }
    }
}
