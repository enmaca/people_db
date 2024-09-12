<?php

namespace App\Console\Commands;

use App\Models\People;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class V1ImportDB
{
    private const CHUNK_SIZE = 1000;
    private array $ine_curps = [];

    public function __construct(private readonly string $path)
    {
        //
    }

    /**
     * The name and signature of the console command.
     *
     * @throws Exception
     */
    public static function version(string $version): bool
    {
        $path_dir = database_path('people_db/'.$version);
        if (! file_exists($path_dir) || ! is_readable($path_dir)) {
            throw new Exception('The file does not exist or is not readable. '.$path_dir);
        }

        $sObj = new self($path_dir);

        $files = scandir($path_dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            switch ($file) {
                case 'Ine_Baja_California_Norte_2018.csv':
                    $sObj->importINE($file);
                    break;
                default:
                    dump('File not implemented '.$file);
            }
        }

        return true;
    }

    private function importINE(mixed $file): void
    {
        echo 'Importing INE file: '.$this->path.'/'.$file;

        $file = fopen($this->path.'/'.$file, 'r');
        $header = fgetcsv($file);
        $batch = [];
        $count = 0;

        try {
            while ($row = fgetcsv($file)) {
                $data = array_combine($header, $row);
                if( $data['curp'] !== null && in_array($data['curp'], array_keys($this->ine_curps)) ) {
                    continue;
                }
                $batch[] = [
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
                    'ine_consec' => $data['consec'],
                    'ine_cred' => $data['cred'],
                    'ine_folio' => $data['folio'],
                ];

                if (count($batch) === self::CHUNK_SIZE) {
                    DB::beginTransaction();
                    dump(People::insert($batch));
                    dump('Total records imported: '.$count);
                    DB::commit();
                    $batch = [];
                }
                $this->ine_curps[$data['curp']] = [];
                $count++;
            }

            if (!empty($batch)) {
                People::insert($batch);
            }

            DB::commit();
            dump('Total records imported: '.$count);
        } catch (Exception $e) {
            echo 'Error: '.$e->getMessage()."\n";
        } finally {
            fclose($file);
        }
    }

    public function parseDateINE($dateString)
    {
        $date = Carbon::createFromFormat('d/m/y H:i:s', $dateString);
        $year = $date->year;

        if ($year > 2000) {
            $year = $year - 100;
        }

        return Carbon::create($year, $date->month, $date->day, $date->hour, $date->minute, $date->second);
    }
}
