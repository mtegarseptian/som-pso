<?php
class SOM {
    private $gridX;
    private $gridY;
    private $learningRate;
    private $sigma;
    private $epochs;
    private $weights;
    private $numFeatures = 7;

    public function __construct($gridX = 5, $gridY = 5, $learningRate = 0.5, $sigma = 1.0, $epochs = 100) {
        $this->gridX = $gridX;
        $this->gridY = $gridY;
        $this->learningRate = $learningRate;
        $this->sigma = $sigma;
        $this->epochs = $epochs;
        $this->initializeWeights();
    }

    private function initializeWeights() {
        $this->weights = [];
        for ($i = 0; $i < $this->gridX; $i++) {
            for ($j = 0; $j < $this->gridY; $j++) {
                for ($k = 0; $k < $this->numFeatures; $k++) {
                    $this->weights[$i][$j][$k] = mt_rand() / mt_getrandmax();
                }
            }
        }
    }

    private function euclideanDistance($a, $b) {
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += pow($a[$i] - $b[$i], 2);
        }
        return sqrt($sum);
    }

    private function findBMU($input) {
        $minDist = PHP_FLOAT_MAX;
        $bmuX = 0;
        $bmuY = 0;
        for ($i = 0; $i < $this->gridX; $i++) {
            for ($j = 0; $j < $this->gridY; $j++) {
                $dist = $this->euclideanDistance($input, $this->weights[$i][$j]);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bmuX = $i;
                    $bmuY = $j;
                }
            }
        }
        return [$bmuX, $bmuY];
    }

    private function neighborhoodFunction($bmuX, $bmuY, $x, $y, $sigma) {
        $dist = pow($x - $bmuX, 2) + pow($y - $bmuY, 2);
        return exp(-$dist / (2 * $sigma * $sigma));
    }

    public function train($data) {
        $n = count($data);
        for ($epoch = 0; $epoch < $this->epochs; $epoch++) {
            $lr = $this->learningRate * exp(-$epoch / $this->epochs);
            $sig = $this->sigma * exp(-$epoch / $this->epochs);
            $idx = array_rand($data);
            $input = $data[$idx];
            [$bmuX, $bmuY] = $this->findBMU($input);
            for ($i = 0; $i < $this->gridX; $i++) {
                for ($j = 0; $j < $this->gridY; $j++) {
                    $h = $this->neighborhoodFunction($bmuX, $bmuY, $i, $j, $sig);
                    for ($k = 0; $k < $this->numFeatures; $k++) {
                        $this->weights[$i][$j][$k] += $lr * $h * ($input[$k] - $this->weights[$i][$j][$k]);
                    }
                }
            }
        }
    }

    public function predict($data) {
        $labels = [];
        foreach ($data as $input) {
            [$bmuX, $bmuY] = $this->findBMU($input);
            $neuronIdx = $bmuX * $this->gridY + $bmuY;
            $labels[] = $neuronIdx;
        }
        return $labels;
    }

    public function getWeights() {
        return $this->weights;
    }

    public function clusterToProductivity($clusterLabels, $originalData) {
        // Hitung rata-rata fitur per cluster
        $clusterFeatures = [];
        $clusterCounts = [];
        foreach ($clusterLabels as $idx => $cluster) {
            if (!isset($clusterFeatures[$cluster])) {
                $clusterFeatures[$cluster] = array_fill(0, $this->numFeatures, 0);
                $clusterCounts[$cluster] = 0;
            }
            for ($k = 0; $k < $this->numFeatures; $k++) {
                $clusterFeatures[$cluster][$k] += $originalData[$idx][$k];
            }
            $clusterCounts[$cluster]++;
        }

        $clusterMeans = [];
        foreach ($clusterFeatures as $c => $feat) {
            $clusterMeans[$c] = array_sum($feat) / ($clusterCounts[$c] * $this->numFeatures);
        }
        asort($clusterMeans);
        $sorted = array_keys($clusterMeans);
        $productivityMap = [];
        $labels = ['rendah', 'sedang', 'tinggi'];
        $step = max(1, intdiv(count($sorted), 3));
        foreach ($sorted as $i => $c) {
            $productivityMap[$c] = $labels[min(intdiv($i * 3, count($sorted)), 2)];
        }
        return $productivityMap;
    }

    // Hitung Silhouette Score (simplified)
    public function silhouetteScore($data, $labels) {
        $n = count($data);
        if ($n < 2) return 0;
        $scores = [];
        $uniqueClusters = array_unique($labels);
        if (count($uniqueClusters) < 2) return 0;

        foreach ($data as $i => $point) {
            $sameCluster = [];
            $otherClusters = [];
            foreach ($data as $j => $other) {
                if ($i === $j) continue;
                $dist = $this->euclideanDistance($point, $other);
                if ($labels[$j] === $labels[$i]) {
                    $sameCluster[] = $dist;
                } else {
                    $otherClusters[$labels[$j]][] = $dist;
                }
            }
            $a = count($sameCluster) > 0 ? array_sum($sameCluster) / count($sameCluster) : 0;
            $b = PHP_FLOAT_MAX;
            foreach ($otherClusters as $clusterDists) {
                $meanDist = array_sum($clusterDists) / count($clusterDists);
                if ($meanDist < $b) $b = $meanDist;
            }
            if ($b === PHP_FLOAT_MAX) $b = 0;
            $s = ($b - $a) / max($a, $b);
            $scores[] = $s;
        }
        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    // Hitung Davies-Bouldin Index (simplified)
    public function daviesBouldinIndex($data, $labels) {
        $uniqueClusters = array_unique($labels);
        $k = count($uniqueClusters);
        if ($k < 2) return 0;

        $centroids = [];
        $scatters = [];
        foreach ($uniqueClusters as $c) {
            $points = [];
            foreach ($data as $i => $point) {
                if ($labels[$i] === $c) $points[] = $point;
            }
            $centroid = array_fill(0, $this->numFeatures, 0);
            foreach ($points as $p) {
                for ($k2 = 0; $k2 < $this->numFeatures; $k2++) {
                    $centroid[$k2] += $p[$k2];
                }
            }
            for ($k2 = 0; $k2 < $this->numFeatures; $k2++) {
                $centroid[$k2] /= count($points);
            }
            $centroids[$c] = $centroid;
            $scatter = 0;
            foreach ($points as $p) {
                $scatter += $this->euclideanDistance($p, $centroid);
            }
            $scatters[$c] = $scatter / count($points);
        }

        $dbSum = 0;
        foreach ($uniqueClusters as $i => $ci) {
            $maxRatio = 0;
            foreach ($uniqueClusters as $j => $cj) {
                if ($ci === $cj) continue;
                $centDist = $this->euclideanDistance($centroids[$ci], $centroids[$cj]);
                if ($centDist > 0) {
                    $ratio = ($scatters[$ci] + $scatters[$cj]) / $centDist;
                    if ($ratio > $maxRatio) $maxRatio = $ratio;
                }
            }
            $dbSum += $maxRatio;
        }
        return $dbSum / $k;
    }
}
?>
