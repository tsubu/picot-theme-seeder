<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Generates a simple screenshot.png (1200x900 layout wireframe) for generated
 * themes so the Appearance > Themes card is not blank. Requires GD; silently
 * skipped when GD is unavailable.
 */
class PTS_Screenshot
{

    /**
     * Create screenshot.png at the given path.
     *
     * @param string $path       Destination file path (screenshot.png).
     * @param string $theme_name Theme name (non-ASCII characters are dropped).
     * @param string $accent     Accent color as #rrggbb.
     * @return bool Whether the file was written.
     */
    public static function create($path, $theme_name, $accent = '#0073aa')
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $w  = 1200;
        $h  = 900;
        $im = imagecreatetruecolor($w, $h);

        list($ar, $ag, $ab) = self::hex_to_rgb($accent, array(0, 115, 170));

        $bg       = imagecolorallocate($im, 255, 255, 255);
        $accent_c = imagecolorallocate($im, $ar, $ag, $ab);
        $light    = imagecolorallocate($im, 243, 244, 246);
        $mid      = imagecolorallocate($im, 229, 231, 235);
        $dark     = imagecolorallocate($im, 75, 85, 99);

        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        // Header band: logo block + nav items.
        imagefilledrectangle($im, 0, 0, $w, 90, $light);
        imagefilledrectangle($im, 60, 35, 220, 55, $accent_c);
        for ($i = 0; $i < 3; $i++) {
            $x = 920 + $i * 80;
            imagefilledrectangle($im, $x, 38, $x + 50, 52, $mid);
        }

        // Hero: headline bars + button.
        imagefilledrectangle($im, 300, 180, 900, 212, $mid);
        imagefilledrectangle($im, 380, 244, 820, 262, $mid);
        imagefilledrectangle($im, 530, 308, 670, 350, $accent_c);

        // Three cards with accent strip.
        $card_y1 = 430;
        $card_y2 = 660;
        foreach (array(60, 430, 800) as $x) {
            imagefilledrectangle($im, $x, $card_y1, $x + 340, $card_y2, $light);
            imagefilledrectangle($im, $x, $card_y1, $x + 340, $card_y1 + 8, $accent_c);
            imagefilledrectangle($im, $x + 24, $card_y1 + 40, $x + 220, $card_y1 + 58, $mid);
            imagefilledrectangle($im, $x + 24, $card_y1 + 80, $x + 316, $card_y1 + 92, $mid);
            imagefilledrectangle($im, $x + 24, $card_y1 + 108, $x + 280, $card_y1 + 120, $mid);
        }

        // Footer band + ASCII theme name label.
        imagefilledrectangle($im, 0, 810, $w, $h, $light);
        $label = trim(preg_replace('/[^\x20-\x7E]/', '', (string) $theme_name));
        if ('' !== $label) {
            imagestring($im, 5, 60, 845, $label, $dark);
        }

        $result = imagepng($im, $path);
        imagedestroy($im);

        return (bool) $result;
    }

    /**
     * @param string             $hex      Color as #rrggbb.
     * @param array<int, int>    $fallback RGB fallback.
     * @return array<int, int>
     */
    private static function hex_to_rgb($hex, $fallback)
    {
        if (! is_string($hex) || ! preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $m)) {
            return $fallback;
        }

        return array(
            hexdec(substr($m[1], 0, 2)),
            hexdec(substr($m[1], 2, 2)),
            hexdec(substr($m[1], 4, 2)),
        );
    }
}
