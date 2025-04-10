<?php
// php implementation of the FindMedian function
function FindMedian($dataset) {
    $count = count($dataset);
    if ($count == 0) return 0;
    
    // sort array
    sort($dataset);
    
    if ($count % 2 == 0) {
        // for even number of elements
        $middle1 = $dataset[($count / 2) - 1];
        $middle2 = $dataset[$count / 2];
        return ($middle1 + $middle2) / 2;
    } else {
        // for odd number of elements
        return $dataset[floor($count / 2)];
    }
}

// php implementation of the FindLowerBound function
function FindLowerBound($dataset) {
    $median = FindMedian($dataset);
    
    // create lower half array
    $lowerHalf = array_filter($dataset, function($val) use ($median) {
        return $val < $median;
    });
    
    if (count($lowerHalf) == 0) return $median;
    
    return FindMedian($lowerHalf);
}

// php implementation of the FindUpperBound function
function FindUpperBound($dataset) {
    $median = FindMedian($dataset);
    
    // create upper half array
    $upperHalf = array_filter($dataset, function($val) use ($median) {
        return $val > $median;
    });
    
    if (count($upperHalf) == 0) return $median;
    
    return FindMedian($upperHalf);
}
?>