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

        ini_set('memory_limit', '32G');

        $sObj->ineCVE = People::select('ine_cve')->get()->pluck('ine_cve')->mapWithKeys(function ($item) {
            return [$item => true];
        })->toArray();
        $files = scandir($path_dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            switch ($file) {
                case 'Ine_Baja_California_Norte_2018.csv':
                case 'Ine_Chiapas_2_2018.csv':
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
        $file = fopen($this->path.'/'.$file, 'r');
        $header = fgetcsv($file);
        $batch = [];
        $count = 0;
        $skipped = 0;
        $skippedBatch = 0;
        while ($row = fgetcsv($file)) {
            $data = array_combine($header, $row);

            if (empty($data['curp'])) {
                $data['curp'] = null;
            }

            if (in_array($data['cve'], array_keys($this->ineCVE))) {
                $skipped++;
                if ($skippedBatch === 1000) {
                    dump('Skipped records: '.$skipped);
                    $skippedBatch = 0;
                }

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
                try {
                    DB::beginTransaction();
                    People::insert($batch);
                    dump('Partial records imported: '.$count);
                    DB::commit();
                    $batch = [];
                } catch (Exception $e) {
                    dump('Error: in records '.$count);

                    continue;
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
                dump('Error: in records '.$count);
            }
        }
        fclose($file);

        dump($this->path.'/'.$file.' => '.$processed_path.'/'.$file);
        dump('Total records imported: '.$count);
        dump('Total records skipped: '.$skipped);
        if (! rename($this->path.'/'.$file, $processed_path.'/'.$file)) {
            throw new Exception('Error moving file to processed directory');
        }
        dd('ok');

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
