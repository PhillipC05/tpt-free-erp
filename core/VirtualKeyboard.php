<?php

namespace TPT\ERP\Core;

/**
 * Virtual Keyboard Manager
 *
 * Provides a secure virtual keyboard to prevent keyloggers during sensitive input
 */
class VirtualKeyboard
{
    private const LAYOUT_QWERTY = 'qwerty';
    private const LAYOUT_NUMERIC = 'numeric';
    private const LAYOUT_RANDOM = 'random';

    /**
     * Generate virtual keyboard HTML and JavaScript
     */
    public function generateKeyboard(
        string $inputId,
        string $layout = self::LAYOUT_QWERTY,
        array $options = []
    ): string {
        $keyboardId = 'vk_' . uniqid();
        $config = array_merge([
            'randomize' => false,
            'showShift' => true,
            'showCapsLock' => true,
            'showSymbols' => true,
            'theme' => 'default',
            'size' => 'medium'
        ], $options);

        $keys = $this->getKeyboardLayout($layout, $config['randomize']);

        $html = $this->generateKeyboardHTML($keyboardId, $inputId, $keys, $config);
        $js = $this->generateKeyboardJS($keyboardId, $inputId, $config);

        return $html . $js;
    }

    /**
     * Get keyboard layout
     */
    private function getKeyboardLayout(string $layout, bool $randomize = false): array
    {
        $layouts = [
            self::LAYOUT_QWERTY => [
                ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
                ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
                ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
                ['z', 'x', 'c', 'v', 'b', 'n', 'm'],
                ['space', 'backspace']
            ],
            self::LAYOUT_NUMERIC => [
                ['1', '2', '3'],
                ['4', '5', '6'],
                ['7', '8', '9'],
                ['0', 'backspace']
            ]
        ];

        $keys = $layouts[$layout] ?? $layouts[self::LAYOUT_QWERTY];

        if ($randomize) {
            $keys = $this->randomizeLayout($keys);
        }

        return $keys;
    }

    /**
     * Randomize keyboard layout for additional security
     */
    private function randomizeLayout(array $keys): array
    {
        $randomized = [];

        foreach ($keys as $row) {
            // Don't randomize special keys
            $specialKeys = ['space', 'backspace', 'shift', 'caps', 'symbols'];
            $regularKeys = array_filter($row, function($key) use ($specialKeys) {
                return !in_array($key, $specialKeys);
            });
            $specialKeysInRow = array_filter($row, function($key) use ($specialKeys) {
                return in_array($key, $specialKeys);
            });

            // Shuffle regular keys
            shuffle($regularKeys);

            // Keep special keys in their positions
            $randomized[] = array_merge($regularKeys, $specialKeysInRow);
        }

        return $randomized;
    }

    /**
     * Generate keyboard HTML
     */
    private function generateKeyboardHTML(
        string $keyboardId,
        string $inputId,
        array $keys,
        array $config
    ): string {
        $html = "<div id='{$keyboardId}' class='virtual-keyboard {$config['theme']} {$config['size']}'>";

        foreach ($keys as $rowIndex => $row) {
            $html .= "<div class='keyboard-row'>";

            foreach ($row as $key) {
                $keyClass = 'keyboard-key';
                $keyText = $key;
                $keyValue = $key;

                switch ($key) {
                    case 'space':
                        $keyClass .= ' key-space';
                        $keyText = 'Space';
                        $keyValue = ' ';
                        break;
                    case 'backspace':
                        $keyClass .= ' key-backspace';
                        $keyText = '⌫';
                        $keyValue = 'backspace';
                        break;
                    case 'shift':
                        $keyClass .= ' key-shift';
                        $keyText = '⇧';
                        $keyValue = 'shift';
                        break;
                    case 'caps':
                        $keyClass .= ' key-caps';
                        $keyText = 'Caps';
                        $keyValue = 'caps';
                        break;
                    case 'symbols':
                        $keyClass .= ' key-symbols';
                        $keyText = '!@#';
                        $keyValue = 'symbols';
                        break;
                }

                $html .= "<button type='button' class='{$keyClass}' data-key='{$keyValue}'>{$keyText}</button>";
            }

            $html .= "</div>";
        }

        $html .= "</div>";

        return $html;
    }

    /**
     * Generate keyboard JavaScript
     */
    private function generateKeyboardJS(string $keyboardId, string $inputId, array $config): string
    {
        $js = "
        <script>
        (function() {
            const keyboard = document.getElementById('{$keyboardId}');
            const input = document.getElementById('{$inputId}');
            let isShift = false;
            let isCaps = false;
            let isSymbols = false;

            if (!keyboard || !input) return;

            // Keyboard state
            const state = {
                shift: false,
                caps: false,
                symbols: false
            };

            // Key mappings
            const keyMappings = {
                'shift': {
                    '1': '!', '2': '@', '3': '#', '4': '$', '5': '%', '6': '^', '7': '&', '8': '*', '9': '(', '0': ')',
                    'q': 'Q', 'w': 'W', 'e': 'E', 'r': 'R', 't': 'T', 'y': 'Y', 'u': 'U', 'i': 'I', 'o': 'O', 'p': 'P',
                    'a': 'A', 's': 'S', 'd': 'D', 'f': 'F', 'g': 'G', 'h': 'H', 'j': 'J', 'k': 'K', 'l': 'L',
                    'z': 'Z', 'x': 'X', 'c': 'C', 'v': 'V', 'b': 'B', 'n': 'N', 'm': 'M'
                },
                'symbols': {
                    '1': '!', '2': '@', '3': '#', '4': '$', '5': '%', '6': '^', '7': '&', '8': '*', '9': '(', '0': ')',
                    'q': '/', 'w': '?', 'e': '=', 'r': '+', 't': '-', 'y': '_', 'u': '[', 'i': ']', 'o': '{', 'p': '}',
                    'a': '\\', 's': '|', 'd': ':', 'f': ';', 'g': '"', 'h': "\\'", 'j': '<', 'k': '>', 'l': ',',
                    'z': '.', 'x': '`', 'c': '~', 'v': '^', 'b': '&', 'n': '*', 'm': '('
                }
            };

            // Handle key press
            function handleKeyPress(key) {
                const currentValue = input.value;
                const cursorPos = input.selectionStart;

                switch (key) {
                    case 'backspace':
                        if (cursorPos > 0) {
                            input.value = currentValue.substring(0, cursorPos - 1) + currentValue.substring(cursorPos);
                            input.selectionStart = input.selectionEnd = cursorPos - 1;
                        }
                        break;
                    case 'space':
                        input.value = currentValue.substring(0, cursorPos) + ' ' + currentValue.substring(cursorPos);
                        input.selectionStart = input.selectionEnd = cursorPos + 1;
                        break;
                    case 'shift':
                        state.shift = !state.shift;
                        updateKeyboardDisplay();
                        break;
                    case 'caps':
                        state.caps = !state.caps;
                        updateKeyboardDisplay();
                        break;
                    case 'symbols':
                        state.symbols = !state.symbols;
                        updateKeyboardDisplay();
                        break;
                    default:
                        let char = key;

                        // Apply shift/caps/symbols transformations
                        if (state.symbols && keyMappings.symbols[key]) {
                            char = keyMappings.symbols[key];
                        } else if (state.shift && keyMappings.shift[key]) {
                            char = keyMappings.shift[key];
                        } else if (state.caps && key.match(/[a-z]/)) {
                            char = key.toUpperCase();
                        }

                        input.value = currentValue.substring(0, cursorPos) + char + currentValue.substring(cursorPos);
                        input.selectionStart = input.selectionEnd = cursorPos + 1;

                        // Reset shift after use
                        if (state.shift) {
                            state.shift = false;
                            updateKeyboardDisplay();
                        }
                        break;
                }

                input.focus();
            }

            // Update keyboard display
            function updateKeyboardDisplay() {
                const keys = keyboard.querySelectorAll('.keyboard-key');

                keys.forEach(key => {
                    const keyValue = key.dataset.key;

                    // Reset classes
                    key.classList.remove('active');

                    // Apply active states
                    if (keyValue === 'shift' && state.shift) {
                        key.classList.add('active');
                    }
                    if (keyValue === 'caps' && state.caps) {
                        key.classList.add('active');
                    }
                    if (keyValue === 'symbols' && state.symbols) {
                        key.classList.add('active');
                    }

                    // Update key display
                    if (keyValue && keyValue.match(/^[a-z0-9]$/)) {
                        let displayText = keyValue;

                        if (state.symbols && keyMappings.symbols[keyValue]) {
                            displayText = keyMappings.symbols[keyValue];
                        } else if (state.shift && keyMappings.shift[keyValue]) {
                            displayText = keyMappings.shift[keyValue];
                        } else if (state.caps) {
                            displayText = keyValue.toUpperCase();
                        }

                        key.textContent = displayText;
                    }
                });
            }

            // Add click handlers
            keyboard.addEventListener('click', function(e) {
                if (e.target.classList.contains('keyboard-key')) {
                    e.preventDefault();
                    const key = e.target.dataset.key;
                    handleKeyPress(key);
                }
            });

            // Prevent context menu on keyboard
            keyboard.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            // Initialize display
            updateKeyboardDisplay();

            // Auto-focus input when keyboard is clicked
            keyboard.addEventListener('mousedown', function() {
                input.focus();
            });

        })();
        </script>
        ";

        return $js;
    }

    /**
     * Generate secure token for keyboard session
     */
    public function generateSessionToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate keyboard session
     */
    public function validateSession(string $token): bool
    {
        // In a real implementation, you'd store tokens in session/cache
        // and validate them here
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    /**
     * Get keyboard statistics for security monitoring
     */
    public function getKeyboardStats(): array
    {
        // This would track usage patterns for security analysis
        return [
            'total_sessions' => 0,
            'average_session_time' => 0,
            'keys_pressed_per_session' => 0,
            'suspicious_patterns_detected' => 0
        ];
    }

    /**
     * Log keyboard usage for security auditing
     */
    public function logKeyboardUsage(int $userId, string $action, array $data = []): void
    {
        $db = Database::getInstance();

        $db->insert('audit_log', [
            'user_id' => $userId,
            'action' => 'virtual_keyboard_' . $action,
            'table_name' => 'virtual_keyboard',
            'record_id' => $userId,
            'description' => 'Virtual keyboard usage: ' . $action,
            'metadata' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
