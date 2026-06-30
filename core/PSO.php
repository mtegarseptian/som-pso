<?php
require_once __DIR__ . '/SOM.php';

class PSO {
    private $numParticles;
    private $maxIterations;
    private $w;   // inertia
    private $c1;  // cognitive
    private $c2;  // social
    private $data;

    // Batas parameter
    private $bounds = [
        'learning_rate' => [0.001, 0.5],
        'sigma'         => [0.5, 3.0],
        'epoch'         => [100, 2000],
    ];

    public function __construct($data, $numParticles = 10, $maxIterations = 30) {
        $this->data = $data;
        $this->numParticles = $numParticles;
        $this->maxIterations = $maxIterations;
        $this->w  = 0.729;
        $this->c1 = 1.494;
        $this->c2 = 1.494;
    }

    private function randomPosition() {
        return [
            'learning_rate' => $this->bounds['learning_rate'][0] + mt_rand() / mt_getrandmax() * ($this->bounds['learning_rate'][1] - $this->bounds['learning_rate'][0]),
            'sigma'         => $this->bounds['sigma'][0] + mt_rand() / mt_getrandmax() * ($this->bounds['sigma'][1] - $this->bounds['sigma'][0]),
            'epoch'         => rand($this->bounds['epoch'][0], $this->bounds['epoch'][1]),
        ];
    }

    private function fitness($position) {
        // Gunakan subset data agar lebih cepat
        $subset = array_slice($this->data, 0, min(200, count($this->data)));
        $som = new SOM(
            3, 3,
            $position['learning_rate'],
            $position['sigma'],
            (int)$position['epoch']
        );
        $som->train($subset);
        $labels = $som->predict($subset);

        $unique = array_unique($labels);
        if (count($unique) < 2) return -1;

        $silhouette = $som->silhouetteScore($subset, $labels);
        return $silhouette; // Maksimalkan silhouette score
    }

    public function optimize() {
        $particles = [];
        $velocities = [];
        $personalBest = [];
        $personalBestFitness = [];
        $globalBest = null;
        $globalBestFitness = -PHP_FLOAT_MAX;
        $history = [];

        // Inisialisasi
        for ($i = 0; $i < $this->numParticles; $i++) {
            $particles[$i] = $this->randomPosition();
            $velocities[$i] = [
                'learning_rate' => 0,
                'sigma'         => 0,
                'epoch'         => 0,
            ];
            $f = $this->fitness($particles[$i]);
            $personalBest[$i] = $particles[$i];
            $personalBestFitness[$i] = $f;
            if ($f > $globalBestFitness) {
                $globalBestFitness = $f;
                $globalBest = $particles[$i];
            }
        }

        // Iterasi PSO
        for ($iter = 0; $iter < $this->maxIterations; $iter++) {
            for ($i = 0; $i < $this->numParticles; $i++) {
                foreach (['learning_rate', 'sigma', 'epoch'] as $key) {
                    $r1 = mt_rand() / mt_getrandmax();
                    $r2 = mt_rand() / mt_getrandmax();
                    $velocities[$i][$key] = $this->w * $velocities[$i][$key]
                        + $this->c1 * $r1 * ($personalBest[$i][$key] - $particles[$i][$key])
                        + $this->c2 * $r2 * ($globalBest[$key] - $particles[$i][$key]);
                    $particles[$i][$key] += $velocities[$i][$key];
                    // Clamp
                    $particles[$i][$key] = max($this->bounds[$key][0], min($this->bounds[$key][1], $particles[$i][$key]));
                }

                $f = $this->fitness($particles[$i]);
                if ($f > $personalBestFitness[$i]) {
                    $personalBest[$i] = $particles[$i];
                    $personalBestFitness[$i] = $f;
                }
                if ($f > $globalBestFitness) {
                    $globalBestFitness = $f;
                    $globalBest = $particles[$i];
                }
            }
            $history[] = [
                'iteration' => $iter + 1,
                'best_fitness' => round($globalBestFitness, 6),
                'best_lr' => round($globalBest['learning_rate'], 6),
                'best_sigma' => round($globalBest['sigma'], 6),
                'best_epoch' => (int)$globalBest['epoch'],
            ];
        }

        return [
            'best_position' => $globalBest,
            'best_fitness'  => $globalBestFitness,
            'history'       => $history,
        ];
    }
}
?>
