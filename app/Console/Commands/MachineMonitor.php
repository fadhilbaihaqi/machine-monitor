<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Models\Reading;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MachineMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'machine:monitor
    {--setup        : Buat tabel database dan tambah 3 mesin contoh}
    {--add-reading= : Tambah pembacaan baru untuk mesin tertentu}
    {--simulate=    : Generate pembacaan acak (default: 10)}
    {--status       : Tampilkan semua mesin dengan data terbaru}
    {--usage        : Tampilkan bantuan penggunaan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor mesin dan tampilkan data pembacaan, dengan opsi simulasi dan penambahan data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('setup')) {
            return $this->setup();
        }

        if ($this->option('add-reading')) {
            return $this->addReading($this->option('add-reading'));
        }

        $simulateOption = $this->option('simulate');
        if ($simulateOption === null || $simulateOption === '') {
            return $this->simulate(10);
        } else {
            return $this->simulate((int) $simulateOption);
        }

        if ($this->option('status')) {
            return $this->status();
        }

        if ($this->option('usage')) {
            return $this->showHelp();
        }

        return $this->showHelp();
    }

    protected function setup()
    {
        Artisan::call('migrate', ['--force' => true]);
        Machine::truncate();
        Machine::insert([
            ['name' => 'Mesin 1', 'location' => 'Lokasi 1', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mesin 2', 'location' => 'Lokasi 2', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mesin 3', 'location' => 'Lokasi 3', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->info("Database berhasil disetup dengan 3 mesin.");
    }

    protected function addReading($machineId)
    {
        $machine = Machine::find($machineId);
        if (!$machine) {
            return $this->error("Mesin tidak ditemukan.");
        }

        $temperature = (float) $this->ask('Masukkan suhu (C)');
        if ($temperature < 20 || $temperature > 100) {
            $this->error('Suhu harus direntang 20-100 C.');
            return;
        }

        $conveyorSpeed = (float) $this->ask('Masukkan kecepatan conveyor (m/menit)');
        if ($conveyorSpeed < 0.5 || $conveyorSpeed > 5.0) {
            $this->error('Kecepatan conveyor harus direntang 0.5-5.0 m/menit.');
            return;
        }

        if ($machine) {
            $machine->readings()->create([
                'machine_id'     => $machine->id,
                'temperature'    => $temperature,
                'conveyor_speed' => $conveyorSpeed,
                'recorded_at'    => now(),
            ]);
        }

        if ($temperature > 80) {
            $this->warn('Peringatan suhu lebih dari 80 C');
        }

        if ($conveyorSpeed < 1.0 || $conveyorSpeed > 4.0) {
            $this->warn('Peringatan kecepatan conveyor diluar rentang aman');
        }

        $this->info("Pembacaan untuk mesin ID {$machine->id} berhasil ditambahkan.");
    }

    protected function simulate($count = 10)
    {
        $machines = Machine::all();

        if ($machines->isEmpty()) {
            return $this->error('Mesin tidak ditemukan.');
        }

        $rows = [];

        foreach ($machines as $m) {
            for ($i = 0; $i < $count; $i++) {
                $reading = Reading::create([
                    'machine_id'     => $m->id,
                    'temperature'    => rand(20, 100),
                    'conveyor_speed' => mt_rand(50, 500) / 100,
                    'recorded_at'    => Carbon::now(),
                ]);

                $rows[] = [
                    'Machine ID'                 => $reading->machine_id,
                    'Name'                       => $reading->machine->name,
                    'Location'                   => $reading->machine->location,
                    'Temperature (C)'            => $reading->temperature,
                    'ConveyorSpeed (m/menit)'    => $reading->conveyor_speed,
                    'Recorded At'                => $reading->recorded_at->format('Y-m-d H:i:s'),
                    'Created At'                 => $reading->created_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        $this->table(
            ['Machine ID', 'Name', 'Location', 'Temperature (C)', 'Conveyor Speed (m/menit)', 'Recorded At', 'Created At'],
            $rows
        );

        $this->info("Simulasi sukses: {$count} pembacaan untuk tiap mesin.");
    }

    protected function status()
    {
        $machines = Machine::with('latestReading')->get();

        $data = [];

        foreach ($machines as $machine) {
            $lastReading = $machine->latestReading;

            if ($lastReading) {
                $temperatureWarning = $lastReading->temperature > 80 ? '⚠️' : '';
                $speedWarning = ($lastReading->conveyor_speed < 1.0 || $lastReading->conveyor_speed > 4.0) ? '⚠️' : '';

                $data[] = [
                    'ID'              => $machine->id,
                    'Nama'            => $machine->name,
                    'Lokasi'          => $machine->location,
                    'Status'          => $machine->status,
                    'Suhu (C)'        => $lastReading->temperature . " $temperatureWarning",
                    'Kecepatan (m/m)' => $lastReading->conveyor_speed . " $speedWarning",
                    'Recorded At'     => $lastReading->recorded_at,
                    'Created At'      => $machine->created_at,
                    'Updated At'      => $machine->updated_at,
                ];
            } else {
                $data[] = [
                    'ID'              => $machine->id,
                    'Nama'            => $machine->name,
                    'Lokasi'          => $machine->location,
                    'Status'          => $machine->status,
                    'Suhu (C)'        => '-',
                    'Kecepatan (m/m)' => '-',
                    'Recorded At'     => '-',
                    'Created At'      => $machine->created_at,
                    'Updated At'      => $machine->updated_at,
                ];
            }
        }

        $this->table(
            ['ID', 'Nama', 'Lokasi', 'Status', 'Suhu (C)', 'Kecepatan (m/m)', 'Recorded At', 'Created At', 'Updated At'],
            $data
        );
    }

    protected function showHelp()
    {
        $this->line('Usage:');
        $this->line('php artisan machine:monitor --setup');
        $this->line('php artisan machine:monitor --add-reading 1');
        $this->line('php artisan machine:monitor --simulate 10');
        $this->line('php artisan machine:monitor --status');
    }
}
