<?php

declare(strict_types=1);

class InvisibleWrapper
{
    const INVISIBLE_CHARACTERS = ["\u{200C}", "\u{200D}"];
    const MESSAGE_END = "\x0A";

    /**
     * Wraps a translation with invisible encoded key data.
     * Call this in development mode only.
     */
    public function wrap($key, $namespace, $translation)
    {
        $data = json_encode([
            'k' => $key,
            'n' => $namespace ?: ''
        ]);

        return $translation . $this->encodeToInvisible($data . self::MESSAGE_END);
    }

    private function encodeToInvisible($text)
    {
        $result = '';
        $bytes = unpack('C*', $text);

        foreach ($bytes as $byte) {
            // Convert byte to 8-bit binary, then add separator bit
            $binary = str_pad(decbin($byte), 8, '0', STR_PAD_LEFT) . '0';

            for ($i = 0; $i < strlen($binary); $i++) {
                $result .= self::INVISIBLE_CHARACTERS[(int)$binary[$i]];
            }
        }

        return $result;
    }
}
