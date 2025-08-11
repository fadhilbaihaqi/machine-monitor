<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Models\Reading;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MachineMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'machine:monitor
    {--setup : Buat tabel database dan tambah 3 mesin contoh}
    {--add-reading= : Tambah pembacaan baru untuk mesin tertentu}
    {--simulate=10 : Generate pembacaan acak (default: 10)}
    {--status : Tampilkan semua mesin dengan data terbaru}
    {--help : Tampilkan bantuan penggunaan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        if ($this->option('simulate')) {
            return $this->simulate((int) $this->option('simulate'));
        }

        if ($this->option('status')) {
            return $this->status();
        }

        return $this->showHelp();
    }

    protected function setup()
    {
        Machine::truncate();
        Machine::insert([
            ['name' => 'Mesin 1', 'location' => 'Lokasi 1', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mesin 2', 'location' => 'Lokasi 2', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mesin 3', 'location' => 'Lokasi 3', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->info('Database berhassil disetup dengan 3 mesin.');
    }

    protected function addReading($machineId)
    {
        $machine = Machine::find($machineId);
        if (!$machine) {
            return $this->error("Mesin tidak ditemukan.");
        }

        if ($machine) {
            $machine->reading()->create([
                'machine_id'     => $machine->id,
                'temperature'    => rand(60, 100),
                'conveyor_speed' => rand(200, 400),
                'recorded_at'    => Carbon::now(),
            ]);
            $this->info('Pembacaan untuk mesin ID {$machineId} berhasil ditambahkan.');
        }
    }

    protected function simulate($count)
    {
        $machines = Machine::all();
        if ($machines->isEmpty()) {
            return $this->error('Mesin tidak ditemukan.');
        }

        foreach ($machines as $m) {
            for ($i = 0; $i < $count; $i++) {
                Reading::create([
                    'machine_id'     => $m->id,
                    'temperature'    => rand(60, 100),
                    'conveyor_speed' => rand(200, 400),
                    'recorded_at'    => Carbon::now(),
                ]);
            }
        }
        $this->info('Simulasi sukses: {$count} untuk tiap mesin');
    }

    protected function status()
    {
        $machines = Machine::with('reading')->get();
        foreach ($machines as $machine) {
            $lastReading = $machine->readings->sortByDesc('recorderd_at')->first();
            $this->line("{$machine->id} | {$machine->name} | {$machine->location} | {$machine->status} ");
            if ($lastReading) {
                $this->line('Terakhir diperbarui: {$lastReading->temperature} C, {$lastReading->conveyor_speed} m/s at {$lastReading->recorded_at}');
            }
        }
    }

    protected function showHelp()
    {
        $this->line('Usage:');
        $this->line('php artisan machine:monitor --setup');
        $this->line('php artisan machine:monitor --add-reading=1');
        $this->line('php artisan machine:monitor --simulate=10');
        $this->line('php artisan machine:monitor --status');
    }
}
