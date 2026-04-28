<?php
$con = file_get_contents('login.php');

$css_add = <<<CSS
.screen-bezel { background: #FDFCF0; border-radius: 42px; overflow: hidden; display: flex; flex-direction: column; height: 780px; position: relative; } 
.status-bar { flex-shrink: 0; background: #000; height: 34px; display: flex; align-items: center; justify-content: space-between; padding: 0 22px 0 18px; position: relative; } 
.punch-hole { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 12px; height: 12px; background: #000; border-radius: 50%; border: 2px solid #1c1c1c; box-shadow: 0 0 0 1px #0a0a0a; } 
.status-time { font-size: 11px; font-weight: 700; color: #fff; } 
.status-icons { display: flex; align-items: center; gap: 4px; } 
.status-icons svg { width: 13px; height: 13px; } 
.home-indicator { flex-shrink: 0; background: #000; height: 26px; display: flex; align-items: center; justify-content: center; } 
.home-bar { width: 90px; height: 4px; background: #3a3a3a; border-radius: 3px; } 
.device-label { margin-top: 18px; color: rgba(255, 255, 255, 0.22); font-size: 10px; letter-spacing: 2.5px; text-transform: uppercase; }
CSS;

$con = preg_replace('/\.screen-bezel\s*\{[^}]*\}/s', $css_add, $con, 1);

if (strpos($con, '.screen-content {') === false) {
    $screen_content_css = ".screen-content { flex: 1; padding: 130px 40px 40px 40px; display: flex; flex-direction: column; align-items: center; }\n.logo-container {";
    $con = str_replace('.logo-container {', $screen_content_css, $con);
}

$status_html = <<<HTML
<div class="status-bar"><div class="punch-hole"></div><span class="status-time">09:41</span><div class="status-icons"><svg viewBox="0 0 16 12" fill="white"><rect x="0" y="8" width="3" height="4" rx="0.5" /><rect x="4" y="5" width="3" height="7" rx="0.5" /><rect x="8" y="2" width="3" height="10" rx="0.5" /><rect x="12" y="0" width="3" height="12" rx="0.5" /></svg><svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"><path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4" /><path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7" /><path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5" /><circle cx="8" cy="11.5" r="0.8" fill="white" /></svg><svg viewBox="0 0 20 12" fill="none"><rect x="0.5" y="0.5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2" /><rect x="2" y="2" width="11" height="8" rx="1" fill="white" /><path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round" /></svg></div></div><div class="screen-content">
HTML;
$con = str_replace('<div class="screen-content">', $status_html, $con);

// Fix the end
$end_html = <<<HTML
</form></div><div class="home-indicator"><div class="home-bar"></div></div></div></div><div class="device-label">Solo Second Thrift &middot; Android Preview</div>
HTML;
$search_end = "</form>\r\n        </div>\r\n    </div>\r\n    </div>";
if (strpos($con, $search_end) !== false) {
    $con = str_replace($search_end, $end_html, $con);
} else {
    // try literal
    $s2 = "</form>\n        </div>\n    </div>\n    </div>";
    $con = str_replace($s2, $end_html, $con);
}

file_put_contents('login.php', $con);
echo "Done";
?>
