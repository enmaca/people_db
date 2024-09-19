<?php

namespace App\Console\Commands;

use App\Models\People;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class V1ImportDB
{
    private const CHUNK_SIZE = 1000;

    private array $ineCVE = [];

    public function __construct(private readonly string $path, private readonly string $version)
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

        $sObj = new self($path_dir, $version);

        $files = scandir($path_dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            switch ($file) {
                case 'Ine_Baja_California_Norte_2018.csv':
                case 'Ine_Baja_California_Sur_2018.csv':
                case 'Ine_Coahuila_2018.csv':
                case 'Ine_Guanajuato_1_2018.csv':
                case 'Ine_Morelos_2018.csv':
                case 'Ine_Chiapas_2_2018.csv':
                case 'Ine_EdoMex_5_2018.csv':
                case 'Ine_Michoacan_1_2018.csv':
                case 'Ine_Puebla_2_2018.csv':
                case 'Ine_Veracruz_1_2018.csv':
                case 'Ine_Chihuahua_2018.csv':
                case 'Ine_EdoMex_6_2018.csv':
                case 'Ine_Michoacan_2_2018.csv':
                case 'Ine_Queretaro_2018.csv':
                case 'Ine_Veracruz_2_2018.csv':
                case 'Ine_QuintanaRoo_2018.csv':
                case 'Ine_Veracruz_3_2018.csv':
                case 'Ine_CDMX_1_2018.csv':
                case 'Ine_Colima_2018.csv':
                case 'Ine_Guanajuato_2_2018.csv':
                case 'Ine_Nayarit_2018.csv':
                case 'Ine_San_Luis_Potosi_2018.csv':
                case 'Ine_Yucatan_2018.csv':
                case 'Ine_CDMX_2_2018.csv':
                case 'Ine_Durango_2018.csv':
                case 'Ine_Guerrero_2018.csv':
                case 'Ine_Nuevo_Leon_1_2018.csv':
                case 'Ine_Sinaloa_2018.csv':
                case 'Ine_Zacatecas_2018.csv':
                case 'Ine_CDMX_3_2018.csv':
                case 'Ine_EdoMex_1_2018.csv':
                case 'Ine_Hidalgo_2018.csv':
                case 'Ine_Nuevo_Leon_2_2018.csv':
                case 'Ine_Sonora_2018.csv':
                case 'Ine_CDMX_4_2018.csv':
                case 'Ine_EdoMex_2_2018.csv':
                case 'Ine_Jalisco_1_2018.csv':
                case 'Ine_Oaxaca_1_2018.csv':
                case 'Ine_Tabasco_2018.csv':
                case 'Ine_Campeche_2018.csv':
                case 'Ine_EdoMex_3_2018.csv':
                case 'Ine_Jalisco_2_2018.csv':
                case 'Ine_Oaxaca_2_2018.csv':
                case 'Ine_Tamaulipas_2018.csv':
                case 'Ine_Chiapas_1_2018.csv':
                case 'Ine_EdoMex_4_2018.csv':
                case 'Ine_Jalisco_3_2018.csv':
                case 'Ine_Puebla_1_2018.csv':
                case 'Ine_Tlaxcala_2018.csv':
                    $sObj->importINE($file);
                    break;
                default:
                    dump('File not implemented '.$file);
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function importINE(mixed $file): void
    {
        echo 'Importing INE file: '.$this->path.'/'.$file;

        $processed_path = database_path('people_db/processed');
        if (! is_dir($processed_path)) {
            if (! mkdir($processed_path, 0777, true)) {
                throw new Exception('Error creating processed directory '.$processed_path);
            }
        }
        $filep = fopen($this->path.'/'.$file, 'r');
        $header = fgetcsv($filep);
        $this->ineCVE = [];
        $batch = [];
        $count = 0;
        $skipped = 0;
        while ($row = fgetcsv($filep)) {
            $data = array_combine($header, $row);

            if (empty($data['curp'])) {
                $data['curp'] = null;
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
                try {
                    DB::beginTransaction();
                    People::insert($batch);
                    dump('Partial records imported: '.$count);
                    DB::commit();
                    $batch = [];
                } catch (Exception $e) {
                    dump('Error: in records '.$count);
                    DB::rollBack();
                    foreach ($batch as $record) {
                        try {
                            dump('Trying to insert record '.$record['ine_cve']);
                            People::create($record);
                        } catch (Exception $e) {
                            dump('Error: '.$e->getMessage());

                            continue;
                        }
                    }
                    $batch = [];
                }
            }
            $this->ineCVE[$data['curp']] = [];
            $count++;
        }

        if (! empty($batch)) {
            try {
                DB::beginTransaction();
                People::insert($batch);
                DB::commit();
                $batch = [];
            } catch (Exception $e) {
                DB::rollBack();
                foreach ($batch as $record) {
                    try {
                        People::create($record);
                    } catch (Exception $e) {
                        dump('Error: '.$e->getMessage());

                        continue;
                    }
                }
            }
        }
        fclose($file);

        dump($this->path.'/'.$file.' => '.$processed_path.'/'.$file);
        dump('Total records imported: '.$count);
        dump('Total records skipped: '.$skipped);
        if (! rename($this->path.'/'.$file, $processed_path.'/'.$file)) {
            throw new Exception('Error moving file to processed directory');
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
