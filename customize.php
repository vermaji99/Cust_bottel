<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$prefillDesign = null;

if (!empty($_GET['design']) && $currentUser) {
    $stmt = db()->prepare('SELECT design_key, meta_json FROM designs WHERE design_key = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$_GET['design'], $currentUser['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $prefillDesign = [
            'design_key' => $row['design_key'],
            'meta' => json_decode($row['meta_json'], true),
        ];
    }
}
$customizerConfig = [
    'base' => app_config('app_url'),
    'prefill' => $prefillDesign,
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
<title>Customize Bottle — Bottel (Pro)</title>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&family=Playfair+Display:wght@400;700&family=Cinzel:wght@400;700&family=Great+Vibes&family=Bebas+Neue&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
  --bg:#070708;
  --card:#0f1112;
  --muted:#bdbdbd;
  --accent:#b9925b;
  --glass: rgba(255,255,255,0.04);
}
*{box-sizing:border-box}
body{margin:0;font-family:Montserrat,system-ui,Arial;background:var(--bg);color:#fff;-webkit-font-smoothing:antialiased}
.container{max-width:1200px;margin:20px auto;padding:18px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.header .title{font-weight:700;color:var(--accent);font-size:1.1rem}
.main{display:flex;gap:20px}
.panel{background:var(--card);padding:16px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.6)}
.left{flex:1}
.right{width:380px;flex-shrink:0}
.canvas-wrap{width:360px;height:760px;margin:0 auto;border-radius:12px;overflow:hidden;background:linear-gradient(180deg,#060606,#0d0d0d);display:flex;align-items:center;justify-content:center;position:relative}
.controls h3{color:var(--accent);margin:0 0 10px}
.row{margin-bottom:12px}
label{display:block;color:var(--muted);font-size:13px;margin-bottom:6px}
input[type="text"], select, input[type="number"], input[type="color"]{
  width:100%;padding:10px;border-radius:8px;border:1px solid #222;background:transparent;color:#fff;
}
.range{width:100%}
.small{font-size:12px;color:#9aa}
.btn{display:inline-block;padding:10px 12px;border-radius:8px;border:none;background:var(--accent);color:#111;font-weight:700;cursor:pointer}
.btn.secondary{background:#222;color:#fff;border:1px solid #333}
.palette{display:flex;gap:8px;align-items:center}
.dot{width:36px;height:36px;border-radius:50%;cursor:pointer;border:2px solid rgba(255,255,255,0.08);box-shadow:0 6px 16px rgba(0,0,0,0.6)}
.flex{display:flex;gap:8px}
.layer-list{margin-top:8px;border-radius:8px;padding:8px;background:var(--glass);max-height:200px;overflow:auto}
.layer-item{padding:8px;border-radius:6px;background:transparent;display:flex;justify-content:space-between;align-items:center;gap:8px;color:#ddd;border:1px solid transparent;border-top: 1px solid transparent; cursor:grab;}
.layer-item.active{background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.03)}
.controls .inline{display:flex;gap:8px}
.controls .inline > *{flex:1}

.canvas-controls{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:10px}
.icon-btn{background:#111;padding:8px;border-radius:8px;border:1px solid #222;color:#fff;cursor:pointer}
.footer-note{font-size:12px;color:#9aa;margin-top:8px;text-align:center}

/* small responsive */
@media(max-width:980px){.main{flex-direction:column}.right{width:100%}}
.preview-overlay{position:absolute;left:0;top:0;right:0;bottom:0;pointer-events:none}

/* NEW ACCORDION CSS */
.accordion-item {
  border-bottom: 1px solid var(--glass);
}
.accordion-header {
  background: var(--glass);
  border: none;
  color: #fff;
  padding: 12px 16px;
  width: 100%;
  text-align: left;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.accordion-header:hover {
  background: rgba(255,255,255,0.06);
}
.accordion-header .fa-chevron-down {
  transition: transform 0.2s;
}
.accordion-header.active .fa-chevron-down {
  transform: rotate(180deg);
}
.accordion-body {
  padding: 16px;
  display: none; /* Default band rakhein */
}
.control-group { 
    margin-bottom: 16px; 
    padding-bottom: 10px;
    border-bottom: 1px solid var(--glass);
}
.control-group:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.control-group h3 { margin: 0 0 10px; color: var(--accent); }
</style>
</head>
<body>
<div class="container">

  <div class="header">
    <div class="title">Bottel — Customizer (Pro)</div>
    <div>
      <a href="index.php" style="color:var(--muted);text-decoration:none;margin-right:10px"><i class="fa fa-arrow-left"></i> Back</a>
      <button id="downloadBtn" class="btn"><i class="fa fa-download"></i> Download PNG</button>
      <button id="reeditBtn" class="btn secondary" style="margin-left:8px"><i class="fa fa-history"></i> Re-Edit</button>
      <button id="saveBtn" class="btn secondary" style="margin-left:8px"><i class="fa fa-save"></i> Save</button>
    </div>
  </div>

  <div id="designStatus" class="small" style="color:#9aa;margin-bottom:10px;"></div>

  <div class="main">
    <div class="panel left">
      <h3 style="margin-top:0">Live Preview</h3>

      <div class="canvas-wrap panel" id="canvasWrap">
        <canvas id="mainCanvas" width="720" height="1520" style="width:360px;height:760px"></canvas>
        <div id="overlay" style="position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none"></div>
      </div>

      <div class="footer-note">Use pointer to drag items; or use sliders for precise control.</div>
    </div>

    <div class="panel right controls">

        <div id="accordion">

          <div class="accordion-item">
            <button class="accordion-header active"> <span><i class="fa fa-flask"></i> Bottle Settings</span>
              <i class="fa fa-chevron-down"></i>
            </button>
            <div class="accordion-body" style="display:block;">
              <div class="row">
                <label>Bottle Shape</label>
                <select id="shape">
                  <option value="vertical">Vertical</option>
                  <option value="rounded">Rounded</option>
                </select>
              </div>
              <div class="row">
                <label>Bottle quick color</label>
                <div class="palette">
                  <div class="dot" data-color="#1e88e5" style="background:#1e88e5"></div>
                  <div class="dot" data-color="#c62828" style="background:#c62828"></div>
                  <div class="dot" data-color="#2e7d32" style="background:#2e7d32"></div>
                  <div class="dot" data-color="#6a1b9a" style="background:#6a1b9a"></div>
                  <div class="dot" data-color="#000000" style="background:#000"></div>
                  <div class="dot" data-color="#ffffff" style="background:#fff;border:2px solid #ddd"></div>
                </div>
              </div>
              <div class="row">
                <label>Export format</label>
                <select id="exportFormat">
                  <option value="png">PNG</option>
                  <option value="jpg">JPG</option>
                </select>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <button class="accordion-header">
              <span><i class="fa fa-plus-circle"></i> Add Layers</span>
              <i class="fa fa-chevron-down"></i>
            </button>
            <div class="accordion-body">
              <div class="row">
                <label>Upload wrapper / label (PNG)</label>
                <input id="uploadLabel" type="file" accept="image/*" />
              </div>
              <div class="row">
                <label>Or choose default wrapper</label>
                <select id="defaultLabel">
                  <option value="assets/images/white-label.png">Default White Label</option>
                  <option value="assets/images/blank-round.png">Rounded Label</option>
                </select>
              </div>
              <hr style="border:none;border-top:1px solid var(--glass);margin:16px 0">
              <div class="row">
                <label>Upload Logo (optional)</label>
                <input id="uploadLogo" type="file" accept="image/*" />
              </div>
              <hr style="border:none;border-top:1px solid var(--glass);margin:16px 0">
              <div class="row">
                <label>Text</label>
                <input id="textInput" type="text" placeholder="Enter name / brand" />
              </div>
              <div class="row">
                <button id="addTextLayer" class="btn secondary" style="width:100%"><i class="fa fa-plus"></i> Add Text Layer</button>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <button class="accordion-header active"> <span><i class="fa fa-layer-group"></i> Layer List</span>
              <i class="fa fa-chevron-down"></i>
            </button>
            <div class="accordion-body" style="display:block;">
                <div class="small">Drag and drop layers to reorder.</div>
                <div class="layer-list" id="layerList"></div>
            </div>
          </div>

          <div class="accordion-item">
            <button class="accordion-header" id="editLayerHeader">
              <span><i class="fa fa-edit"></i> Edit Selected Layer</span>
              <i class="fa fa-chevron-down"></i>
            </button>
            <div class="accordion-body" id="selectedLayerControlsBody">

                <div id="textControls" class="control-group" style="display:none;">
                    <h3>Text Properties</h3>
                    <div class="row">
                        <label>Font family</label>
                        <select id="fontFamily">
                            <option value="Montserrat">Montserrat</option>
                            <option value="Playfair Display">Playfair Display</option>
                            <option value="Cinzel">Cinzel</option>
                            <option value="Great Vibes">Great Vibes</option>
                            <option value="Bebas Neue">Bebas Neue</option>
                        </select>
                    </div>
                    <div class="row inline">
                        <div style="flex:1">
                            <label>Font size</label>
                            <input id="fontSize" type="range" min="10" max="120" class="range" />
                        </div>
                        <div style="width:80px">
                            <label>Size</label>
                            <input id="fontSizeNum" type="number" min="8" max="200" style="width:100%;padding:8px;border-radius:6px;border:1px solid #222" />
                        </div>
                    </div>
                    <div class="row">
                        <label>Text color</label>
                        <input id="textColor" type="color" value="#ffffff" />
                    </div>
                    <div class="row">
                        <label>Text style</label>
                        <select id="textStyle">
                            <option value="none">None</option>
                            <option value="shadow">Drop shadow</option>
                            <option value="stroke">Outline</option>
                        </select>
                    </div>
                    <div id="strokeControls" class="row" style="display:none; background: var(--glass); padding: 8px; border-radius: 8px;">
                      <div class="inline">
                        <div>
                          <label>Stroke Color</label>
                          <input id="strokeColor" type="color" value="#b9965c" />
                        </div>
                        <div>
                          <label>Stroke Width</label>
                          <input id="strokeWidth" type="number" value="2" min="1" max="20" />
                        </div>
                      </div>
                    </div>
                </div>

                <div id="imageControls" class="control-group" style="display:none;">
                    <h3>Image Filters</h3>
                    <div class="row">
                      <label>Opacity (1-100)</label>
                      <input id="opacity" type="range" min="1" max="100" value="100" class="range" />
                    </div>
                    <div class="row">
                      <label>Brightness (0-200%)</label>
                      <input id="brightness" type="range" min="0" max="200" value="100" class="range" />
                    </div>

                    <div class="row" id="logoWidthRow" style="display:none;">
      <label>Logo Width Stretch (%)</label>
      <input id="logoWidthStretch" type="range" min="20" max="200" value="100" class="range" />
    </div>

    <div class="row" id="logoHeightRow" style="display:none;">
      <label>Logo Height Stretch (%)</label>
      <input id="logoHeightStretch" type="range" min="20" max="200" value="100" class="range" />
    </div>

                    <div class="row" id="labelWidthRow" style="display:none;"> <label>Label Width (% of bottle)</label>
      <input id="labelWidth" type="range" min="20" max="110" value="62" class="range" />
    </div>
    <div class="row" id="labelHeightRow" style="display:none;">
      <label>Label Height Stretch (%)</label>
      <input id="labelHeight" type="range" min="20" max="200" value="100" class="range" />
    </div>

                </div>

                <div id="layerControls" class="control-group" style="display:none">
                    <h3>Transform</h3>
                    <div class="row">
                      <label>Selected X</label>
                      <input id="posX" type="range" min="-180" max="180" value="0" class="range" />
                    </div>
                    <div class="row">
                      <label>Selected Y</label>
                      <input id="posY" type="range" min="-300" max="300" value="0" class="range" />
                    </div>
                    <div class="row inline">
                      <div>
                        <label>Scale</label>
                        <input id="scale" type="range" min="10" max="300" value="100" class="range" />
                      </div>
                      <div style="width:80px">
                        <label>Rotate</label>
                        <input id="rotate" type="number" min="0" max="360" value="0" style="width:100%;padding:8px;border-radius:6px;border:1px solid #222" />
                      </div>
                    </div>
                    <div class="row inline">
                      <button id="bringFront" class="btn secondary">Bring Front</button>
                      <button id="sendBack" class="btn secondary">Send Back</button>
                    </div>
                </div>
                
                <div class="small">Tip: Click a layer in the list, then drag it on preview, or use sliders for precision.</div>

            </div>
          </div>


        </div> </div>

  </div>
</div>

<script>
window.BOTTEL_CUSTOMIZER = <?= json_encode($customizerConfig, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script>
/* --------------------
  Utilities & state
---------------------*/
const canvas = document.getElementById('mainCanvas');
const ctx = canvas.getContext('2d');

// Hi-DPI scaling
const DPR = window.devicePixelRatio > 1 ? 2 : 1;
canvas.width = 360 * DPR;
canvas.height = 760 * DPR;
canvas.style.width = '360px';
canvas.style.height = '760px';
ctx.scale(DPR, DPR);

// base assets
const BASE = {
  vertical: 'assets/images/blank-vertical.png',
  rounded:  'assets/images/blank-round.png'
};
let baseImages = { vertical: null, rounded: null };

// layer model
let layers = [];

/* default initial state */
let state = {
  bottleShape: 'vertical',
  bottleColor: '#1e88e5',
  selectedLayerId: null,
  textDefaults: { fontFamily:'Montserrat', fontSize:36, color:'#fff', style:'none' }
};

/* DOM refs */
const shapeEl = document.getElementById('shape');
const dots = document.querySelectorAll('.dot');
const uploadLabelEl = document.getElementById('uploadLabel');
const defaultLabelEl = document.getElementById('defaultLabel');
const uploadLogoEl = document.getElementById('uploadLogo');
const textInput = document.getElementById('textInput');
const fontFamilyEl = document.getElementById('fontFamily');
const fontSizeRange = document.getElementById('fontSize');
const fontSizeNum = document.getElementById('fontSizeNum');
const textColorEl = document.getElementById('textColor');
const textStyleEl = document.getElementById('textStyle');
const layerListEl = document.getElementById('layerList');

// New control refs
const textControlsEl = document.getElementById('textControls');
const imageControlsEl = document.getElementById('imageControls');
const strokeControlsEl = document.getElementById('strokeControls');
const opacityEl = document.getElementById('opacity');
const brightnessEl = document.getElementById('brightness');
const labelWidthRowEl = document.getElementById('labelWidthRow'); // NEW
const labelWidthEl = document.getElementById('labelWidth');       // NEW
const labelHeightRowEl = document.getElementById('labelHeightRow'); // NEW
const labelHeightEl = document.getElementById('labelHeight');       // NEW
const logoWidthRowEl = document.getElementById('logoWidthRow');     // NEW
const logoWidthStretchEl = document.getElementById('logoWidthStretch'); // NEW
const logoHeightRowEl = document.getElementById('logoHeightRow');   // NEW
const logoHeightStretchEl = document.getElementById('logoHeightStretch'); // NEW
const strokeColorEl = document.getElementById('strokeColor');
const strokeWidthEl = document.getElementById('strokeWidth');

const layerControls = document.getElementById('layerControls');
const posX = document.getElementById('posX');
const posY = document.getElementById('posY');
const scaleEl = document.getElementById('scale');
const rotateEl = document.getElementById('rotate');
const bringFrontBtn = document.getElementById('bringFront');
const sendBackBtn = document.getElementById('sendBack');

const addTextLayerBtn = document.getElementById('addTextLayer');
const downloadBtn = document.getElementById('downloadBtn');
const saveBtn = document.getElementById('saveBtn');

/* small helpers */
function uid(prefix='id'){ return prefix + Math.random().toString(36).slice(2,9); }
function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }
function readFileAsDataURL(file){
  return new Promise((resolve, reject)=>{
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(file);
  });
}
function hydrateImageLayer(layer){
  if (layer.type === 'label' || layer.type === 'logo') {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      layer.w = img.width;
      layer.h = img.height;
      render();
      refreshLayerList();
    };
    img.src = layer.imgSrc;
    layer.img = img;
  }
  return layer;
}

/* --------------------
  Base image loading (with onload)
---------------------*/
function loadBaseImages(){
  baseImages.vertical = new Image();
  baseImages.vertical.crossOrigin = 'anonymous';
  baseImages.vertical.onload = () => render();
  baseImages.vertical.src = BASE.vertical;

  baseImages.rounded = new Image();
  baseImages.rounded.crossOrigin = 'anonymous';
  baseImages.rounded.onload = () => render();
  baseImages.rounded.src = BASE.rounded;
}
loadBaseImages();

/* --------------------
  Layer creation helpers
---------------------*/
function addLabelLayerFromImage(imgSrc){
  if (!imgSrc) return;
  const id = uid('label_');
  const layer = { 
    id,
    type:'label',
    imgSrc,
    x:0,
    y:0,
    scale:100,
    rotation:0,
    visible:true,
    w:null,
    h:null,
    tint:null,
    widthPx:null,
    opacity: 100,
    brightness: 100,
    labelWidthPercent: 62,
    labelHeightStretch: 100
  };
  hydrateImageLayer(layer);
  layers.push(layer);
  selectLayer(id);
  refreshLayerList();
}

function addLogoLayerFromImage(imgSrc){
  if (!imgSrc) return;
  const id = uid('logo_');
  const layer = { 
    id,
    type:'logo',
    imgSrc,
    x:0,
    y:40,
    scale:70,
    rotation:0,
    visible:true,
    w:null,
    h:null,
    widthPx:null,
    opacity: 100,
    brightness: 100,
    logoWidthStretch: 100,
    logoHeightStretch: 100
  };
  hydrateImageLayer(layer);
  layers.push(layer);
  selectLayer(id);
  refreshLayerList();
}

function addTextLayer(text){
  const id = uid('text_');
  const layer = {
    id, type:'text', text: text || 'Your Name',
    x:0, y:40, scale:100, rotation:0, visible:true,
    fontFamily: state.textDefaults.fontFamily,
    fontSize: state.textDefaults.fontSize,
    color: state.textDefaults.color,
    style: state.textDefaults.style,
    strokeColor: '#b9965c', // NEW
    strokeWidth: 2           // NEW
  };
  layers.push(layer);
  selectLayer(id);
  refreshLayerList();
  render();
}

/* --------------------
  Layer list UI (UPDATED for Drag-n-Drop)
---------------------*/
function refreshLayerList(){
  layerListEl.innerHTML = '';
  // draw from bottom (0) to top (end)
  layers.forEach((l, index) => { // Added index
    const div = document.createElement('div');
    div.className = 'layer-item' + (state.selectedLayerId===l.id ? ' active' : '');
    div.setAttribute('draggable', 'true'); // NEW
    div.dataset.layerId = l.id;            // NEW
    div.dataset.index = index;             // NEW

    div.innerHTML = `<div style="display:flex;gap:8px;align-items:center;pointer-events:none;"> <strong style="font-size:13px">${l.type.toUpperCase()}</strong>
      <div style="opacity:.7;margin-left:6px;font-size:12px">${l.type==='text'? (l.text.length>18? l.text.slice(0,18)+'…':l.text) : l.img? 'image' : ''}</div>
    </div>
    <div style="display:flex;gap:6px">
      <button title="Select" class="icon-btn" data-id="${l.id}"><i class="fa fa-hand-pointer"></i></button>
      <button title="Toggle" class="icon-btn" data-toggle="${l.id}"><i class="fa fa-eye"></i></button>
      <button title="Delete" class="icon-btn" data-del="${l.id}"><i class="fa fa-trash"></i></button>
    </div>`;
    layerListEl.appendChild(div);
  });

  // attach events
  layerListEl.querySelectorAll('[data-id]').forEach(btn => {
    btn.onclick = () => selectLayer(btn.getAttribute('data-id'));
  });
  layerListEl.querySelectorAll('[data-toggle]').forEach(btn => {
    btn.onclick = () => toggleVisibility(btn.getAttribute('data-toggle'));
  });
  layerListEl.querySelectorAll('[data-del]').forEach(btn => {
    btn.onclick = () => deleteLayer(btn.getAttribute('data-del'));
  });
}

// NEW: Drag and Drop Listeners
let dragStartIndex;
layerListEl.addEventListener('dragstart', (e) => {
    if (e.target.classList.contains('layer-item')) {
        dragStartIndex = parseInt(e.target.dataset.index, 10);
        e.target.style.opacity = '0.4'; // Pata chale ki drag ho raha hai
    }
});
layerListEl.addEventListener('dragover', (e) => {
    e.preventDefault(); // Drop allow karne ke liye
    const target = e.target.closest('.layer-item');
    if (target) {
        // Clear previous highlights
        document.querySelectorAll('.layer-item').forEach(item => item.style.borderTop = '1px solid transparent');
        target.style.borderTop = '2px solid var(--accent)'; // Kahan drop hoga
    }
});
layerListEl.addEventListener('dragleave', (e) => {
    const target = e.target.closest('.layer-item');
    if (target) {
        target.style.borderTop = '1px solid transparent'; // Reset
    }
});
layerListEl.addEventListener('dragend', (e) => {
    if (e.target.classList.contains('layer-item')) e.target.style.opacity = '1'; // Reset
    document.querySelectorAll('.layer-item').forEach(item => item.style.borderTop = '1px solid transparent');
});
layerListEl.addEventListener('drop', (e) => {
    e.preventDefault();
    const target = e.target.closest('.layer-item');
    if (!target) return;
    
    const dropIndex = parseInt(target.dataset.index, 10);
    target.style.borderTop = '1px solid transparent';

    // Array ko move/reorder karein
    const [draggedLayer] = layers.splice(dragStartIndex, 1);
    layers.splice(dropIndex, 0, draggedLayer);

    // Sab kuch refresh karein
    refreshLayerList();
    render();
});


/* --------------------
  Layer manipulations (UPDATED)
---------------------*/
function selectLayer(id){
  state.selectedLayerId = id;
  refreshLayerList();
  updateLayerControls();
  render();

  // NEW: Accordion control
  const editSectionHeader = document.getElementById('editLayerHeader');
  const editSectionBody = document.getElementById('selectedLayerControlsBody');
  const sel = findLayer(id);
  
  if (id && sel) {
    // Open the accordion
    if (!editSectionHeader.classList.contains('active')) {
      editSectionHeader.classList.add('active');
      editSectionBody.style.display = 'block';
    }
    // Show/hide specific control groups
    textControlsEl.style.display = (sel.type === 'text') ? 'block' : 'none';
    imageControlsEl.style.display = (sel.type === 'label' || sel.type === 'logo') ? 'block' : 'none';
    labelWidthRowEl.style.display = (sel.type === 'label') ? 'block' : 'none';
    labelHeightRowEl.style.display = (sel.type === 'label') ? 'block' : 'none';
    logoWidthRowEl.style.display = (sel.type === 'logo') ? 'block' : 'none';
    logoHeightRowEl.style.display = (sel.type === 'logo') ? 'block' : 'none';
    strokeControlsEl.style.display = (sel.type === 'text' && sel.style === 'stroke') ? 'block' : 'none';

  } else {
    // Hide all if no layer selected
    textControlsEl.style.display = 'none';
    imageControlsEl.style.display = 'none';
    labelWidthRowEl.style.display = 'none';
    labelHeightRowEl.style.display = 'none';
    logoWidthRowEl.style.display = 'none';
    logoHeightRowEl.style.display = 'none';
    strokeControlsEl.style.display = 'none';
  }
}

function findLayer(id){ return layers.find(l=>l.id===id); }

function deleteLayer(id){
  layers = layers.filter(l=>l.id!==id);
  if (state.selectedLayerId === id) {
    state.selectedLayerId = layers.length ? layers[layers.length-1].id : null;
    selectLayer(state.selectedLayerId); // Call selectLayer to update UI
  } else {
    refreshLayerList();
    render();
  }
}

function toggleVisibility(id){
  const l = findLayer(id); if (!l) return;
  l.visible = !l.visible;
  refreshLayerList(); render();
}

function bringFront(id){
  const idx = layers.findIndex(l=>l.id===id); if (idx<0) return;
  const [l] = layers.splice(idx,1);
  layers.push(l);
  refreshLayerList(); render();
}
function sendBack(id){
  const idx = layers.findIndex(l=>l.id===id); if (idx<0) return;
  const [l] = layers.splice(idx,1);
  layers.unshift(l);
  refreshLayerList(); render();
}

/* --------------------
  Render all: (UPDATED with filters and stroke)
---------------------*/
function render(){
  ctx.clearRect(0,0,360,760);
  ctx.fillStyle = '#070708';
  ctx.fillRect(0,0,360,760);

  const base = baseImages[state.bottleShape];
  if (!base || !base.complete) {
    ctx.fillStyle = '#0d0d0d';
    ctx.fillRect(30,20,300,720);
  } else {
    const targetW = 300;
    const scale = targetW / base.width;
    const targetH = base.height * scale;
    const x = (360 - targetW) / 2;
    const y = (760 - targetH) / 2 - 6;
    ctx.drawImage(base, x, y, targetW, targetH);

    ctx.save();
    ctx.globalCompositeOperation = 'multiply';
    ctx.fillStyle = state.bottleColor || '#1e88e5';
    ctx.fillRect(x,y,targetW,targetH);
    ctx.restore();

    const bottleBox = { x,y,w:targetW,h:targetH };

    for (const layer of layers){
      if (!layer.visible) continue;

      ctx.save();
      const cx = bottleBox.x + bottleBox.w/2 + (layer.x || 0);
      const cy = bottleBox.y + bottleBox.h*0.33 + (layer.y || 0);
      const s = (layer.scale||100)/100;

      ctx.translate(cx, cy);
      ctx.rotate((layer.rotation||0) * Math.PI/180);
      ctx.scale(s, s);

      if (layer.type === 'label' && layer.img && layer.img.complete){
        // NEW: Apply filters
        ctx.globalAlpha = (layer.opacity || 100) / 100;
        ctx.filter = `brightness(${layer.brightness || 100}%)`;

        const labelWidthPercent = (layer.labelWidthPercent || 62) / 100;
        const desiredW = bottleBox.w * labelWidthPercent;

        const drawW = desiredW / s;
        
        // YEH NAYI LOGIC HAI (Height Stretch ke saath)
        const aspect = (layer.img.height && layer.img.width) ? (layer.img.height / layer.img.width) : 1;
        const baseDrawH = drawW * aspect; // Original height
        const heightStretch = (layer.labelHeightStretch || 100) / 100; // Stretch factor
        const drawH = baseDrawH * heightStretch; // Stretched height
        
        ctx.drawImage(layer.img, -drawW/2, -drawH/2, drawW, drawH);
        
        ctx.globalAlpha = 1.0; // Reset
        ctx.filter = 'none';   // Reset

        if (layer.tint){
          ctx.globalCompositeOperation = 'source-atop';
          ctx.fillStyle = layer.tint;
          // Nayi height yahan bhi use karein
          ctx.fillRect(-drawW/2, -drawH/2, drawW, drawH);
        }
      } else if (layer.type === 'logo' && layer.img && layer.img.complete){
    // NEW: Apply filters
    ctx.globalAlpha = (layer.opacity || 100) / 100;
    ctx.filter = `brightness(${layer.brightness || 100}%)`;

    const maxW = 120;
    
    // YEH NAYI LOGIC HAI (Logo Stretch)
    const baseDrawW = maxW / s; // Base width scaled by general 'Scale'
    const widthStretch = (layer.logoWidthStretch || 100) / 100;
    const heightStretch = (layer.logoHeightStretch || 100) / 100;

    const drawW = baseDrawW * widthStretch; // Apply Width Stretch
    const aspect = layer.img.height / layer.img.width;
    const baseDrawH = baseDrawW * aspect;
    const drawH = baseDrawH * heightStretch; // Apply Height Stretch
    
    ctx.drawImage(layer.img, -drawW/2, -drawH/2, drawW, drawH);
    
    ctx.globalAlpha = 1.0; // Reset
    ctx.filter = 'none';   // Reset
} else if (layer.type === 'text'){
        const fontSize = (layer.fontSize||36);
        ctx.font = `${fontSize}px ${layer.fontFamily || 'Montserrat'}`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        if (layer.style === 'shadow'){
          ctx.shadowColor = 'rgba(0,0,0,0.6)';
          ctx.shadowBlur = 12;
          ctx.fillStyle = layer.color || '#fff';
          ctx.fillText(layer.text || '', 0, 0);
          ctx.shadowBlur = 0;
        } else if (layer.style === 'stroke'){ // UPDATED
          ctx.lineWidth = layer.strokeWidth || 2;
          ctx.strokeStyle = layer.strokeColor || '#b9965c';
          ctx.strokeText(layer.text || '', 0, 0);
          ctx.fillStyle = layer.color || '#fff';
          ctx.fillText(layer.text || '', 0, 0);
        } else {
          ctx.fillStyle = layer.color || '#fff';
          ctx.fillText(layer.text || '', 0, 0);
        }
      }
      ctx.restore();
    }
  }

  drawOverlay();
}

/* --------------------
  Overlay
---------------------*/
const overlay = document.getElementById('overlay');
function drawOverlay(){
  overlay.innerHTML = '';
  const sel = findLayer(state.selectedLayerId);
  if (!sel) return;

  const base = baseImages[state.bottleShape];
  const targetW = 300;
  const scale = base && base.width ? (targetW / base.width) : 1;
  const targetH = base ? base.height * scale : 400;
  const x = (360 - targetW) / 2;
  const y = (760 - targetH) / 2 - 6;

  const cx = x + targetW/2 + (sel.x||0);
  const cy = y + targetH*0.33 + (sel.y||0);

  const box = document.createElement('div');
  box.style.position = 'absolute';
  box.style.left = (cx - 80) + 'px';
  box.style.top = (cy - 30) + 'px';
  box.style.width = '160px';
  box.style.height = '60px';
  box.style.border = '1px dashed rgba(255,255,255,0.25)';
  box.style.borderRadius = '6px';
  box.style.pointerEvents = 'none';
  overlay.appendChild(box);
}

/* --------------------
  Pointer drag
---------------------*/
let dragging = false;
let dragStart = null;

function screenToModel(clientX, clientY){
  const rect = canvas.getBoundingClientRect();
  const x = clientX - rect.left;
  const y = clientY - rect.top;
  const mx = x * (360 / rect.width);
  const my = y * (760 / rect.height);
  return {mx,my};
}

canvas.addEventListener('pointerdown', (ev)=>{
  const sel = findLayer(state.selectedLayerId);
  if (!sel) return;
  ev.preventDefault();
  dragging = true;
  dragStart = screenToModel(ev.clientX, ev.clientY);
  canvas.setPointerCapture(ev.pointerId);
});
window.addEventListener('pointermove', (ev)=>{
  if (!dragging || !dragStart) return;
  const sel = findLayer(state.selectedLayerId);
  if (!sel) return;
  const pos = screenToModel(ev.clientX, ev.clientY);
  const dx = pos.mx - dragStart.mx;
  const dy = pos.my - dragStart.my;
  sel.x = (sel.x || 0) + dx;
  sel.y = (sel.y || 0) + dy;
  dragStart = pos;
  updateControlsFromSelected();
  render();
});
window.addEventListener('pointerup', (ev)=>{
  if (dragging) { dragging = false; dragStart = null; }
});

/* --------------------
  Sync helpers (UPDATED)
---------------------*/
function updateLayerControls(){
  const sel = findLayer(state.selectedLayerId);
  if (!sel){
    layerControls.style.display = 'none';
    return;
  }
  layerControls.style.display = 'block';
  posX.value = sel.x || 0;
  posY.value = sel.y || 0;
  scaleEl.value = sel.scale || 100;
  rotateEl.value = sel.rotation || 0;

  if (sel.type === 'text'){
    textInput.value = sel.text || '';
    fontFamilyEl.value = sel.fontFamily || 'Montserrat';
    fontSizeRange.value = sel.fontSize || 36;
    fontSizeNum.value = sel.fontSize || 36;
    textColorEl.value = sel.color || '#ffffff';
    textStyleEl.value = sel.style || 'none';
    // NEW stroke
    strokeColorEl.value = sel.strokeColor || '#b9965c';
    strokeWidthEl.value = sel.strokeWidth || 2;
  } else if (sel.type === 'label' || sel.type === 'logo') {
    // NEW filters
    opacityEl.value = sel.opacity || 100;
    brightnessEl.value = sel.brightness || 100;
  }
  if (sel.type === 'label') {
        labelWidthEl.value = sel.labelWidthPercent || 62;
        labelHeightEl.value = sel.labelHeightStretch || 100;
    }
    else if (sel.type === 'logo') { // YEH NAYA LOGIC ADD KAREIN
        logoWidthStretchEl.value = sel.logoWidthStretch || 100;
        logoHeightStretchEl.value = sel.logoHeightStretch || 100;
    }
}

function updateControlsFromSelected(){
  const sel = findLayer(state.selectedLayerId);
  if (!sel) return;
  posX.value = Math.round(sel.x || 0);
  posY.value = Math.round(sel.y || 0);
  scaleEl.value = sel.scale || 100;
  rotateEl.value = sel.rotation || 0;

  if (sel.type === 'text'){
    fontSizeRange.value = sel.fontSize || 36;
    fontSizeNum.value = sel.fontSize || 36;
  }
}

/* wire layer control inputs */
posX.oninput = () => { const sel = findLayer(state.selectedLayerId); if(!sel) return; sel.x = parseInt(posX.value,10); render(); };
posY.oninput = () => { const sel = findLayer(state.selectedLayerId); if(!sel) return; sel.y = parseInt(posY.value,10); render(); };
scaleEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(!sel) return; sel.scale = parseInt(scaleEl.value,10); render(); };
rotateEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(!sel) return; sel.rotation = parseFloat(rotateEl.value); render(); };

/* --------------------
  UI events (UPDATED)
---------------------*/
shapeEl.onchange = (e) => { state.bottleShape = e.target.value; render(); };
dots.forEach(d => { d.onclick = () => { state.bottleColor = d.dataset.color; render(); }; });

uploadLabelEl.onchange = async (e) => {
  const f = e.target.files[0];
  if (!f) return;
  const dataUrl = await readFileAsDataURL(f);
  addLabelLayerFromImage(dataUrl);
};

defaultLabelEl.onchange = (e) => {
  if (!e.target.value) return;
  addLabelLayerFromImage(e.target.value);
};

uploadLogoEl.onchange = async (e) => {
  const f = e.target.files[0];
  if (!f) return;
  const dataUrl = await readFileAsDataURL(f);
  addLogoLayerFromImage(dataUrl);
};

/* text input / add text */
textInput.onchange = () => {
  const sel = findLayer(state.selectedLayerId);
  if (sel && sel.type === 'text') {
    sel.text = textInput.value;
    render();
    refreshLayerList(); // Update text preview in list
  } else {
    addTextLayer(textInput.value);
  }
};
addTextLayerBtn.onclick = () => addTextLayer(textInput.value || 'Your Name');

/* font family / size / color / style */
fontFamilyEl.onchange = () => { const sel = findLayer(state.selectedLayerId); if (sel && sel.type==='text'){ sel.fontFamily = fontFamilyEl.value; render(); } };
fontSizeRange.oninput = () => { fontSizeNum.value = fontSizeRange.value; const sel = findLayer(state.selectedLayerId); if (sel && sel.type==='text'){ sel.fontSize = parseInt(fontSizeRange.value,10); render(); } };
fontSizeNum.oninput = () => { fontSizeRange.value = fontSizeNum.value; const sel = findLayer(state.selectedLayerId); if (sel && sel.type==='text'){ sel.fontSize = parseInt(fontSizeNum.value,10); render(); } };
textColorEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if (sel && sel.type==='text'){ sel.color = textColorEl.value; render(); } };
textStyleEl.onchange = () => { 
    const sel = findLayer(state.selectedLayerId); 
    if (sel && sel.type==='text'){ 
        sel.style = textStyleEl.value; 
        // NEW: Show/hide stroke controls
        strokeControlsEl.style.display = (sel.style === 'stroke') ? 'block' : 'none';
        render(); 
    } 
};

// NEW: Stroke and Filter events
strokeColorEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel && sel.type==='text') { sel.strokeColor = strokeColorEl.value; render(); }};
strokeWidthEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel && sel.type==='text') { sel.strokeWidth = parseInt(strokeWidthEl.value,10); render(); }};
opacityEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel) { sel.opacity = parseInt(opacityEl.value,10); render(); }};
brightnessEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel) { sel.brightness = parseInt(brightnessEl.value,10); render(); }};
labelWidthEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel && sel.type==='label') { sel.labelWidthPercent = parseInt(labelWidthEl.value,10); render(); }};
labelHeightEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel && sel.type==='label') { sel.labelHeightStretch = parseInt(labelHeightEl.value,10); render(); }};
logoWidthStretchEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel && sel.type==='logo') { sel.logoWidthStretch = parseInt(logoWidthStretchEl.value,10); render(); }};
logoHeightStretchEl.oninput = () => { const sel = findLayer(state.selectedLayerId); if(sel && sel.type==='logo') { sel.logoHeightStretch = parseInt(logoHeightStretchEl.value,10); render(); }};

bringFrontBtn.onclick = () => { if (state.selectedLayerId) bringFront(state.selectedLayerId); };
sendBackBtn.onclick = () => { if (state.selectedLayerId) sendBack(state.selectedLayerId); };

/* initial defaults for text range */
fontSizeRange.value = 36;
fontSizeNum.value = 36;

/* --------------------
  Save / Download (UPDATED)
---------------------*/
downloadBtn.onclick = async () => {
  render();
  const exportDPR = 2;
  const W = 360 * exportDPR;
  const H = 760 * exportDPR;
  const ex = document.createElement('canvas');
  ex.width = W;
  ex.height = H;
  const exCtx = ex.getContext('2d');
  exCtx.scale(exportDPR, exportDPR);

  // draw same logic
  const base = baseImages[state.bottleShape];
  if (base && base.complete){
    const targetW = 300;
    const scale = targetW / base.width;
    const targetH = base.height * scale;
    const x = (360 - targetW) / 2;
    const y = (760 - targetH) / 2 - 6;
    exCtx.drawImage(base, x, y, targetW, targetH);
    exCtx.save();
    exCtx.globalCompositeOperation = 'multiply';
    exCtx.fillStyle = state.bottleColor;
    exCtx.fillRect(x,y,targetW,targetH);
    exCtx.restore();

    const bottleBox = { x,y,w:targetW,h:targetH };

    for (const layer of layers){
      if (!layer.visible) continue;
      exCtx.save();
      const cx = bottleBox.x + bottleBox.w/2 + (layer.x||0);
      const cy = bottleBox.y + bottleBox.h*0.33 + (layer.y||0);
      const s = (layer.scale||100)/100;
      exCtx.translate(cx,cy);
      exCtx.rotate((layer.rotation||0) * Math.PI/180);
      exCtx.scale(s,s);

      if (layer.type==='label' && layer.img && layer.img.complete){
        exCtx.globalAlpha = (layer.opacity || 100) / 100;
        exCtx.filter = `brightness(${layer.brightness || 100}%)`;
      
        const desiredW = (layer.widthPx)? layer.widthPx : bottleBox.w * 0.62;
        const drawW = desiredW / s;
        const aspect = layer.img.height / layer.img.width;
        const drawH = drawW * aspect;
        exCtx.drawImage(layer.img, -drawW/2, -drawH/2, drawW, drawH);
        
        exCtx.globalAlpha = 1.0;
        exCtx.filter = 'none';

        if (layer.tint){
          exCtx.globalCompositeOperation = 'source-atop';
          exCtx.fillStyle = layer.tint;
          exCtx.fillRect(-drawW/2, -drawH/2, drawW, drawH);
        }
      } else if (layer.type==='logo' && layer.img && layer.img.complete){
        exCtx.globalAlpha = (layer.opacity || 100) / 100;
        exCtx.filter = `brightness(${layer.brightness || 100}%)`;

        const maxW = 120;
        const drawW = (layer.widthPx) ? layer.widthPx / s : maxW / s;
        const aspect = layer.img.height / layer.img.width;
        const drawH = drawW * aspect;
        exCtx.drawImage(layer.img, -drawW/2, -drawH/2, drawW, drawH);
        
        exCtx.globalAlpha = 1.0;
        exCtx.filter = 'none';
      } else if (layer.type==='text'){
        const fontSize = (layer.fontSize||36);
        exCtx.font = `${fontSize}px ${layer.fontFamily || 'Montserrat'}`;
        exCtx.textAlign = 'center';
        exCtx.textBaseline = 'middle';
        if (layer.style === 'shadow'){
          exCtx.shadowColor = 'rgba(0,0,0,0.6)';
          exCtx.shadowBlur = 12;
          exCtx.fillStyle = layer.color || '#fff';
          exCtx.fillText(layer.text || '', 0, 0);
          exCtx.shadowBlur = 0;
        } else if (layer.style === 'stroke'){ // UPDATED
          exCtx.lineWidth = layer.strokeWidth || 2;
          exCtx.strokeStyle = layer.strokeColor || '#b9965c';
          exCtx.strokeText(layer.text || '', 0, 0);
          exCtx.fillStyle = layer.color || '#fff';
          exCtx.fillText(layer.text || '', 0, 0);
        } else {
          exCtx.fillStyle = layer.color || '#fff';
          exCtx.fillText(layer.text || '', 0, 0);
        }
      }
      exCtx.restore();
    }
  }

  const url = ex.toDataURL('image/png');
  const a = document.createElement('a');
  a.href = url;
  a.download = 'bottel-design.png';
  a.click();
};

function serializeLayers(){
  return layers.map(layer => {
    const plain = { ...layer };
    delete plain.img;
    return plain;
  });
}

function loadLayersFromMeta(metaLayers = []){
  layers = [];
  metaLayers.forEach(layer => {
    const hydrated = hydrateImageLayer({ ...layer });
    layers.push(hydrated);
  });
  refreshLayerList();
  render();
}

/* --------------------
  Init: add default layers
---------------------*/
document.addEventListener('DOMContentLoaded', ()=>{
  const defaultSrc = defaultLabelEl.value || 'assets/images/white-label.png';
  addLabelLayerFromImage(defaultSrc);
  addTextLayer('Your Brand');
  refreshLayerList();
  render();
});

window.BottelCustomizer = {
  exportMeta() {
    return {
      bottleShape: state.bottleShape,
      bottleColor: state.bottleColor,
      layers: serializeLayers(),
    };
  },
  importMeta(meta) {
    if (!meta) return;
    state.bottleShape = meta.bottleShape || state.bottleShape;
    state.bottleColor = meta.bottleColor || state.bottleColor;
    if (Array.isArray(meta.layers)) {
      loadLayersFromMeta(meta.layers);
    } else {
      render();
    }
  },
  render,
};

/* small pointer cursor */
canvas.style.cursor = 'grab';

/* --------------------
  NEW: Accordion JS
---------------------*/
document.querySelectorAll('.accordion-header').forEach(header => {
  header.addEventListener('click', () => {
    const body = header.nextElementSibling;
    const isActive = header.classList.contains('active');
    
    // Optional: Close all others
    // document.querySelectorAll('.accordion-header').forEach(h => {
    //   if (h !== header) {
    //      h.classList.remove('active');
    //      h.nextElementSibling.style.display = 'none';
    //   }
    // });

    if (!isActive) {
      header.classList.add('active');
      body.style.display = 'block';
    } else {
        header.classList.remove('active');
        body.style.display = 'none';
    }
  });
});

</script>
<script src="assets/js/customize.js"></script>
<script src="assets/js/app.js" defer></script>
</body>
</html>