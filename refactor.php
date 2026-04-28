<?php

$new_body_css = <<<EOD
body {
    background: #12121f;
    background-image:
        radial-gradient(ellipse at 15% 50%, rgba(38, 70, 83, 0.45) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 15%, rgba(178, 58, 72, 0.25) 0%, transparent 50%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    font-family: {font};
}
EOD;

$new_wrapper_css = <<<EOD
{selector} {
    position: relative;
    width: 393px;
    background: linear-gradient(160deg, #3a3a3a 0%, #1e1e1e 50%, #111 100%);
    border-radius: 54px;
    padding: 15px;
    box-shadow:
        0 0 0 1.5px #4a4a4a,
        0 0 0 3px #1a1a1a,
        6px 6px 0 4px #000,
        0 40px 100px rgba(0, 0, 0, 0.85),
        inset 0 2px 0 rgba(255, 255, 255, 0.1);
}

.btn-power { position: absolute; right: -5px; top: 140px; width: 5px; height: 55px; background: linear-gradient(to right, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 0 4px 4px 0; }
.btn-vol-up { position: absolute; left: -5px; top: 120px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px; }
.btn-vol-down { position: absolute; left: -5px; top: 172px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px; }

.screen-bezel {
    background: #FDFCF0;
    border-radius: 42px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 780px;
    position: relative;
}
EOD;

$files = glob("*.php");
$skip = ['dashboardcrew.php', 'koneksi.php', 'logout.php', 'test_run.php'];

foreach ($files as $file) {
    if (in_array($file, $skip)) continue;

    $content = file_get_contents($file);
    
    // Extract font-family
    $font = "'Inter', sans-serif"; // default
    if (preg_match('/body\s*\{[^}]*font-family:\s*([^;]+);/i', $content, $matches)) {
        $font = trim($matches[1]);
    }
    
    // Replace body CSS
    $body_replacement = str_replace('{font}', $font, $new_body_css);
    $content = preg_replace('/body\s*\{[^}]*\}/i', $body_replacement, $content, 1);
    
    // Find wrapper class
    if (preg_match('/\.((?:phone|phone-mockup|android-device|mockup))\s*\{[^}]*\}/i', $content, $wm)) {
        $div_class = $wm[1];
        $selector = '.' . $div_class;
        
        // Replace wrapper CSS
        $wrapper_replacement = str_replace('{selector}', $selector, $new_wrapper_css);
        $content = preg_replace('/\.((?:phone|phone-mockup|android-device|mockup))\s*\{[^}]*\}/i', $wrapper_replacement, $content, 1);
        
        // Inject HTML
        $html_inject = '<div class="' . $div_class . '">' . "\n" .
                       '    <!-- Physical Buttons -->' . "\n" .
                       '    <div class="btn-power"></div>' . "\n" .
                       '    <div class="btn-vol-up"></div>' . "\n" .
                       '    <div class="btn-vol-down"></div>' . "\n" .
                       '    <div class="screen-bezel">';
                       
        $div_pattern = '/<div\s+class=["\']' . preg_quote($div_class, '/') . '["\']\s*>/i';
        if (preg_match($div_pattern, $content)) {
            $content = preg_replace($div_pattern, $html_inject, $content, 1);
            
            // Insert closing div before the last </div> that is before </body>
            $idx = strrpos($content, '</body>');
            if ($idx !== false) {
                $substr = substr($content, 0, $idx);
                $last_div_idx = strrpos($substr, '</div>');
                if ($last_div_idx !== false) {
                    $content = substr($content, 0, $last_div_idx) . "</div>\n    " . substr($content, $last_div_idx);
                }
            }
            
            file_put_contents($file, $content);
            echo "Processed $file\n";
        } else {
            echo "Skipped $file (could not find div)\n";
        }
    } else {
        echo "Skipped $file (could not find wrapper CSS)\n";
    }
}
?>
