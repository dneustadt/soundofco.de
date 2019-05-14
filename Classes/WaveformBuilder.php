<?php

namespace Classes;

class WaveformBuilder
{
    const NOTES = [
        0 => 130.8,
        1 => 138.6,
        2 => 146.8,
        3 => 155.6,
        4 => 164.8,
        5 => 174.6,
        6 => 185.0,
        7 => 196.0,
        8 => 207.7,
        9 => 220.0,
        10 => 233.1,
        11 => 246.9,
        12 => 261.6,
        13 => 277.2,
        14 => 293.7,
        15 => 311.1,
        16 => 329.6,
        17 => 349.2,
        18 => 370.0,
        19 => 392.0,
        20 => 415.3,
        21 => 440.0,
        22 => 466.2,
        23 => 493.9,
        24 => 523.3,
        25 => 554.4,
        26 => 587.3,
        27 => 622.3,
        28 => 659.3,
        29 => 698.5,
        30 => 740.0,
        31 => 784.0,
        32 => 830.6,
        33 => 880.0,
        34 => 932.3,
        35 => 987.8
    ];

    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function contentLinesToArray()
    {
        $lines = explode("\n", $this->content);

        return $lines;
    }

    public function convertLinesToBars($lines)
    {
        foreach ($lines as &$line) {
            $line = strlen($line);
        }

        return $this->rescaleBars($lines);
    }

    private function rescaleBars($bars)
    {
        $srcMin = min($bars);
        $srcMax = max($bars);

        $destMin = 0;
        $destMax = count(self::NOTES) - 1;

        foreach($bars as &$bar)
        {
            $pos = (($bar - $srcMin) / (($srcMax - $srcMin) ?: 1));
            $bar = self::NOTES[(int) round(($pos * ($destMax - $destMin)) + $destMin)];
        }

        return array_values($bars);
    }
}