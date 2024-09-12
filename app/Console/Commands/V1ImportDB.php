<?php

namespace App\Console\Commands;

use App\Models\People;
use Carbon\Carbon;
use Exception;

class V1ImportDB
{

    public function __construct(private readonly string $path)
    {
        //
    }
    /**
     * The name and signature of the console command.
     *
     * @param string $version
     * @return bool
     * @throws Exception
     */
    public static function version(string $version): bool
    {
        $path_dir = database_path('people_db/'. $version) ;
        if (!file_exists($path_dir) || !is_readable($path_dir)) {
            throw new Exception('The file does not exist or is not readable. ' . $path_dir);
        }

        $sObj = new self($path_dir);

        $files = scandir($path_dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            switch($file) {
                case 'Ine_Baja_California_Norte_2018.csv':
                    $sObj->importINE($file);
                    break;
                default:
                    throw new Exception('File not implemented '. $file);
            }
        }


        return true;
    }

    private function importINE(mixed $file)
    {
        echo "Importing INE file: ". $this->path."/".$file;

        $file = fopen($this->path."/".$file, 'r');
        $header = fgetcsv($file);
        $count = 0;
        while ($row = fgetcsv($file)) {
            $data = array_combine($header, $row);
            $count++;
            try {
                People::create([
                    'edad' => intval($data['edad']),
                    'nombre' => $data['nombre'],
                    'paterno' => $data['paterno'],
                    'materno' => $data['materno'],
                    'fecha_nacimiento' => $this->parseDateINE($data['fecnac']),
                    'sexo' => $data['sexo'],
                    'calle' => $data['calle'],
                    'curp' => empty($data['curp']) ? null : $data['curp'],
                    'int' => $data['int'],
                    'ext' => $data['ext'],
                    'colonia' => $data['colonia'],
                    'cp' => intval($data['cp']),
                    'ine_cve' => $data['cve'],
                    'ine_e' => intval($data['e']),
                    'ine_d' => intval($data['d']),
                    'ine_m' => intval($data['m']),
                    'ine_s' => intval($data['s']),
                    'ine_l' => intval($data['l']),
                    'ine_mza' => intval($data['mza']),
                    'ine_consec'    => $data['consec'],
                    'ine_cred' => $data['cred'],
                    'ine_folio' => $data['folio']
                ])->save();
                print("Record created: ". $data['curp'] ." count: ".$count."\n");
            } catch (Exception $e) {
                print("Error: ". $e->getMessage()."\n");
            }
        }
    }

    function parseDateINE($dateString)
    {
        $date = Carbon::createFromFormat('d/m/y H:i:s', $dateString);
        $year = $date->year;

        if( $year > 2000){
            $year = $year - 100;
        }

        return Carbon::create($year, $date->month, $date->day, $date->hour, $date->minute, $date->second);
    }
}
