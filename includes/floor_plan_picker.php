<?php
/**
 * Floor Plan Picker — reusable room-selection module
 *
 * Set these PHP variables BEFORE including this file:
 *   $fp_id        (string)  Unique prefix used for element IDs, e.g. 'res' or 'fac'
 *   $fp_buildings (array)   [{id, name}, …]  from DB
 *   $fp_div_key   (string)  localStorage key for persisting pane width
 *   $fp_default_w (int)     Initial pane width in px  (default 600)
 *
 * After the include, initialise the JS class:
 *   const picker = new FloorPlanPicker({
 *       paneId      : '<?= $fp_id ?>-fp-pane',
 *       dividerId   : '<?= $fp_id ?>-fp-divider',
 *       dividerKey  : '<?= $fp_div_key ?>',
 *       defaultWidth: <?= $fp_default_w ?>,
 *       linkedGroups: [],         // [{id, name, room_ids:[…]}]
 *       onChange    : rooms => {} // called with selected-rooms map on every change
 *       onRoomClick : (room, floor) => {} // optional: called when a room polygon is clicked
 *   });
 */
$fp_id        = $fp_id        ?? 'fp';
$fp_div_key   = $fp_div_key   ?? ('fp_w_' . $fp_id);
$fp_default_w = $fp_default_w ?? 600;
?>

<?php /* ── Styles (output once; guard handled by browser ignoring duplicate <style>) ── */ ?>
<style id="floor-plan-picker-styles">
/* ── Floor Plan Picker module ─────────────────────────────── */
.fp-pane {
    flex-shrink: 0;
    background: #f1f5f9;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
}
.fp-divider {
    width: 5px;
    flex-shrink: 0;
    background: #e2e8f0;
    cursor: col-resize;
    position: relative;
    z-index: 10;
    transition: background .12s;
}
.fp-divider:hover, .fp-divider.dragging { background: #3b82f6; }
.fp-divider::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 1px; height: 36px;
    background: rgba(255,255,255,0.7);
    border-radius: 1px;
}
.fp-hdr {
    padding: 8px 8px 6px;
    border-bottom: 1px solid #d1d5db;
    background: #e8ecf0;
    flex-shrink: 0;
}
.fp-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #6b7280;
    margin-bottom: 5px;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
.fp-bld-sel {
    width: 100%;
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    padding: 4px 7px;
    font-size: 11px;
    font-family: ui-sans-serif, system-ui, sans-serif;
    cursor: pointer;
    outline: none;
    margin-bottom: 5px;
}
.fp-bld-sel:focus { border-color: #3b82f6; }
.fp-deselect-btn {
    width: 100%;
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    border-radius: 5px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    font-family: ui-sans-serif, system-ui, sans-serif;
    transition: background .12s, color .12s;
}
.fp-deselect-btn:hover { background: #fee2e2; color: #b91c1c; }
.fp-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.fp-floor-card {
    background: white;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.fp-floor-card-hdr {
    padding: 5px 8px;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
.fp-floor-order {
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    background: #e5e7eb;
    border-radius: 4px;
    padding: 1px 4px;
    flex-shrink: 0;
}
.fp-bld-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #9ca3af;
    padding: 6px 4px 2px;
    font-family: ui-sans-serif, system-ui, sans-serif;
    flex-shrink: 0;
}
.fp-empty, .fp-loading {
    padding: 20px 8px;
    text-align: center;
    font-size: 11px;
    color: #9ca3af;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
.fp-empty a { color: #3b82f6; }
.fp-no-rooms {
    padding: 8px;
    font-size: 11px;
    color: #9ca3af;
    text-align: center;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
</style>

<?php /* ── HTML ── */ ?>
<div id="<?= htmlspecialchars($fp_id) ?>-fp-pane"
     class="fp-pane"
     style="width:<?= (int)$fp_default_w ?>px; min-width:200px; max-width:1200px;">

    <div class="fp-hdr">
        <div class="fp-title">Floor Plans</div>
        <select class="fp-bld-sel">
            <option value="">All Buildings</option>
            <?php foreach ($fp_buildings as $b): ?>
            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="fp-deselect-btn" type="button">Deselect All</button>
    </div>

    <div class="fp-list">
        <div class="fp-loading">Loading…</div>
    </div>
</div>

<div id="<?= htmlspecialchars($fp_id) ?>-fp-divider" class="fp-divider"></div>

<?php /* ── JS class (guarded so it's only defined once even if included twice) ── */ ?>
<script>
if (!window.FloorPlanPicker) {
(function() {

class FloorPlanPicker {
    constructor(opts = {}) {
        this._paneEl    = document.getElementById(opts.paneId);
        this._divEl     = document.getElementById(opts.dividerId);
        this._divKey    = opts.dividerKey   || 'fp_pane_w';
        this._minW      = opts.minWidth     || 200;
        this._maxW      = opts.maxWidth     || 1200;
        this._onChange    = opts.onChange     || (() => {});
        this._onRoomClick = opts.onRoomClick  || null;

        this._rooms     = {};   // { id: {id,name,floor,building} } — selected rooms
        this._floors    = [];   // loaded [{id,name,floor_order,building_id,building_name,rooms:[]}]
        this._bldFilter = null; // numeric building_id or null = all
        this._linked    = [];   // [{id,name,building_id,room_ids:[…]}]

        if (!this._paneEl) { console.warn('FloorPlanPicker: pane element not found:', opts.paneId); return; }

        // Restore saved width
        const saved = parseInt(localStorage.getItem(this._divKey));
        if (saved >= this._minW && saved <= this._maxW)
            this._paneEl.style.width = saved + 'px';

        // Building dropdown
        const sel = this._paneEl.querySelector('.fp-bld-sel');
        if (sel) sel.addEventListener('change', () => {
            this._bldFilter = parseInt(sel.value) || null;
            this.load();
        });

        // Deselect button
        const dBtn = this._paneEl.querySelector('.fp-deselect-btn');
        if (dBtn) dBtn.addEventListener('click', () => this.clearSelection());

        // Divider drag
        if (this._divEl) this._initDivider();

        // Initial load
        this.load();
    }

    // ── Public API ────────────────────────────────────────────

    /** Replace the linked-groups list and re-render. */
    setLinkedGroups(groups) {
        this._linked = groups || [];
        this._render();
    }

    /** Returns a shallow copy of the selected-rooms map. */
    getSelection() { return Object.assign({}, this._rooms); }

    /** Pre-select rooms by ID array (expands linked groups). */
    selectRooms(ids) {
        ids.forEach(id => {
            const r = this._findRoom(id);
            if (r) this._rooms[id] = { id, name: r.name, floor: r._floorName, building: r._bldName };
        });
        this._expandLinked();
        this._render();
    }

    /** Deselect everything. */
    clearSelection() {
        this._rooms = {};
        this._render();
        this._onChange(this._rooms);
    }

    /** Reload floor/room data from the server. */
    async load() {
        const list = this._paneEl.querySelector('.fp-list');
        if (list) list.innerHTML = '<div class="fp-loading">Loading\u2026</div>';
        try {
            const url = this._bldFilter
                ? `/api/floor_editor_api.php?action=get_building_floors_rooms&building_id=${this._bldFilter}`
                : '/api/floor_editor_api.php?action=get_all_floors_rooms';
            this._floors = await fetch(url).then(r => r.json());
        } catch(e) { this._floors = []; }
        this._render();
    }

    /** Re-render SVGs without fetching. */
    refresh() { this._render(); }

    // ── Private helpers ───────────────────────────────────────

    _initDivider() {
        const div = this._divEl, pane = this._paneEl;
        let dragging = false, startX = 0, startW = 0;
        div.addEventListener('mousedown', e => {
            e.preventDefault(); dragging = true;
            startX = e.clientX; startW = pane.offsetWidth;
            div.classList.add('dragging');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', e => {
            if (!dragging) return;
            pane.style.width = Math.max(this._minW, Math.min(this._maxW, startW + e.clientX - startX)) + 'px';
        });
        document.addEventListener('mouseup', () => {
            if (!dragging) return;
            dragging = false; div.classList.remove('dragging');
            document.body.style.cursor = ''; document.body.style.userSelect = '';
            localStorage.setItem(this._divKey, pane.offsetWidth);
            this._render();
        });
    }

    _findRoom(id) {
        for (const floor of this._floors) {
            const r = floor.rooms.find(x => x.id == id);
            if (r) return Object.assign({}, r, { _floorName: floor.name, _bldName: floor.building_name || '' });
        }
        return null;
    }

    /** Returns the link group that contains roomId, or null. */
    _groupFor(id) {
        return this._linked.find(g => g.room_ids.includes(Number(id))) || null;
    }

    /** When pre-selecting, expand any linked groups. */
    _expandLinked() {
        const ids = Object.keys(this._rooms).map(Number);
        for (const g of this._linked) {
            if (g.room_ids.some(id => ids.includes(id))) {
                g.room_ids.forEach(rid => {
                    const r = this._findRoom(rid);
                    if (r) this._rooms[rid] = { id: rid, name: r.name, floor: r._floorName, building: r._bldName };
                });
            }
        }
    }

    _toggle(id, name, floorName, bldName) {
        const isSel = !!this._rooms[id];
        const group = this._groupFor(id);
        if (group) {
            // Toggle the whole linked group as a unit
            if (isSel) {
                group.room_ids.forEach(rid => delete this._rooms[rid]);
            } else {
                group.room_ids.forEach(rid => {
                    const r = this._findRoom(rid);
                    if (r) this._rooms[rid] = { id: rid, name: r.name, floor: r._floorName, building: r._bldName };
                });
            }
        } else {
            if (isSel) delete this._rooms[id];
            else this._rooms[id] = { id, name, floor: floorName, building: bldName };
        }
        this._render();
        this._onChange(this._rooms);
    }

    _render() {
        const list = this._paneEl.querySelector('.fp-list');
        if (!list) return;
        list.innerHTML = '';

        if (!this._floors.length) {
            list.innerHTML = '<div class="fp-empty">No floors yet.<br><a href="/pages/facilities.php">Add in Facilities \u2192</a></div>';
            return;
        }

        const paneInnerW = list.clientWidth || 576;
        const PAD_PX     = 8;

        // Compute global bounding box anchored to 0,0
        const floorBounds = this._floors.map(floor => {
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            for (const room of floor.rooms) {
                if (!room.map_points) continue;
                for (const [x, y] of room.map_points) {
                    minX = Math.min(minX, x); minY = Math.min(minY, y);
                    maxX = Math.max(maxX, x); maxY = Math.max(maxY, y);
                }
            }
            return isFinite(minX) ? { minX, minY, maxX, maxY, w: maxX - minX, h: maxY - minY } : null;
        });

        const globalMinX  = Math.min(0, floorBounds.reduce((mn, b) => b ? Math.min(mn, b.minX) : mn, 0));
        const globalMaxX  = floorBounds.reduce((mx, b) => b ? Math.max(mx, b.maxX) : mx, 0);
        const globalSpan  = globalMaxX - globalMinX;
        const globalScale = globalSpan > 0 ? (paneInnerW - PAD_PX * 2) / globalSpan : 4;
        const padSVG      = PAD_PX / globalScale;
        const sharedVx    = globalMinX - padSVG;
        const sharedVw    = globalSpan + padSVG * 2;

        let lastBldId = null;
        this._floors.forEach((floor, idx) => {
            const bounds = floorBounds[idx];

            // Building label (when showing all buildings)
            if (this._bldFilter === null && floor.building_id !== lastBldId) {
                lastBldId = floor.building_id;
                const lbl = document.createElement('div');
                lbl.className = 'fp-bld-label';
                lbl.textContent = floor.building_name || `Building ${floor.building_id}`;
                list.appendChild(lbl);
            }

            const card = document.createElement('div');
            card.className = 'fp-floor-card';

            const hdr = document.createElement('div');
            hdr.className = 'fp-floor-card-hdr';
            hdr.innerHTML = `<span class="fp-floor-order">${floor.floor_order}</span><span>${_fpEsc(floor.name)}</span>`;
            card.appendChild(hdr);

            if (!bounds) {
                const empty = document.createElement('div');
                empty.className = 'fp-no-rooms';
                empty.textContent = 'No rooms mapped';
                card.appendChild(empty);
            } else {
                const vy   = bounds.minY - padSVG;
                const vh   = bounds.h + padSVG * 2;
                const svgH = Math.round(vh * globalScale);

                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('viewBox', `${sharedVx} ${vy} ${sharedVw} ${vh}`);
                svg.setAttribute('width', '100%');
                svg.setAttribute('height', svgH);
                svg.setAttribute('preserveAspectRatio', 'xMinYMin meet');
                svg.style.display = 'block';

                for (const room of floor.rooms) {
                    if (!room.map_points || room.map_points.length < 3) continue;

                    const isSel   = !!this._rooms[room.id];

                    // Colour scheme: selected → green, unselected → blue
                    let fill, stroke, strokeW;
                    if (isSel) {
                        fill = 'rgba(34,197,94,0.35)'; stroke = '#16a34a'; strokeW = '0.8';
                    } else {
                        fill = 'rgba(59,130,246,0.15)';  stroke = '#1d4ed8'; strokeW = '0.5';
                    }

                    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                    g.style.cursor = 'pointer';

                    const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                    poly.setAttribute('points', room.map_points.map(([x, y]) => `${x},${y}`).join(' '));
                    poly.setAttribute('fill',   fill);
                    poly.setAttribute('stroke', stroke);
                    poly.setAttribute('stroke-width', strokeW);
                    poly.setAttribute('vector-effect', 'non-scaling-stroke');
                    g.appendChild(poly);

                    // Room label
                    const label = room.abbreviation || _fpAbbrev(room.name);
                    if (label) {
                        const rb = _fpBounds(room.map_points);
                        const fs = Math.max(1.5, Math.min(4, Math.min(rb.maxX - rb.minX, rb.maxY - rb.minY) * 0.22));
                        const [lx, ly] = _fpCenter(room.map_points);
                        const t = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        t.setAttribute('x', lx); t.setAttribute('y', ly);
                        t.setAttribute('text-anchor', 'middle');
                        t.setAttribute('dominant-baseline', 'middle');
                        t.setAttribute('fill', isSel ? '#14532d' : '#1e3a5f');
                        t.setAttribute('font-size', String(fs));
                        t.setAttribute('font-weight', '700');
                        t.setAttribute('pointer-events', 'none');
                        t.style.fontFamily = 'ui-sans-serif,system-ui,sans-serif';
                        t.textContent = label;
                        g.appendChild(t);
                    }

                    g.addEventListener('click', () => {
                        if (this._onRoomClick) this._onRoomClick(room, floor);
                        this._toggle(room.id, room.name, floor.name, floor.building_name || '');
                    });
                    svg.appendChild(g);
                }
                card.appendChild(svg);
            }
            list.appendChild(card);
        });
    }
}

// ── Module-level helpers (not on prototype to avoid name collisions) ──
function _fpEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function _fpBounds(pts) {
    let minX=Infinity,minY=Infinity,maxX=-Infinity,maxY=-Infinity;
    for (const [x,y] of pts) { minX=Math.min(minX,x);minY=Math.min(minY,y);maxX=Math.max(maxX,x);maxY=Math.max(maxY,y); }
    return {minX,minY,maxX,maxY};
}
// Pole of inaccessibility — the point furthest from all edges (Mapbox polylabel algorithm).
// Returns [x, y] that is maximally interior, ideal for labels on irregular shapes.
function _fpCenter(pts) {
    let mnX=Infinity,mnY=Infinity,mxX=-Infinity,mxY=-Infinity;
    for (const [x,y] of pts) { mnX=Math.min(mnX,x);mnY=Math.min(mnY,y);mxX=Math.max(mxX,x);mxY=Math.max(mxY,y); }
    const W=mxX-mnX, H=mxY-mnY;
    const h0 = Math.min(W,H)/2;
    if (!h0) return [(mnX+mxX)/2,(mnY+mxY)/2];
    const prec = h0/16;

    // Max-heap keyed on "potential" = center_dist + half_diag
    const heap = [];
    const _push = c => {
        heap.push(c);
        let i=heap.length-1;
        while (i>0) { const p=(i-1)>>1; if (heap[p][4]>=heap[i][4]) break; [heap[p],heap[i]]=[heap[i],heap[p]]; i=p; }
    };
    const _pop = () => {
        const top=heap[0]; const last=heap.pop();
        if (heap.length) {
            heap[0]=last; let i=0;
            for (;;) {
                let j=2*i+1; if (j>=heap.length) break;
                if (j+1<heap.length && heap[j+1][4]>heap[j][4]) j++;
                if (heap[i][4]>=heap[j][4]) break;
                [heap[i],heap[j]]=[heap[j],heap[i]]; i=j;
            }
        }
        return top;
    };
    const _cell = (x,y,h) => { const d=_fpDist(x,y,pts); return [x,y,h,d,d+h*1.4142]; };

    // Seed: one cell per quadrant of the bounding box
    for (let x=mnX+h0; x<mxX; x+=h0*2)
        for (let y=mnY+h0; y<mxY; y+=h0*2)
            _push(_cell(x,y,h0));

    // Start best at bounding-box centre
    let bx=(mnX+mxX)/2, by=(mnY+mxY)/2, bd=_fpDist(bx,by,pts);

    while (heap.length) {
        const [cx,cy,h,d,pot] = _pop();
        if (d > bd) { bd=d; bx=cx; by=cy; }
        if (pot-bd <= prec) continue;           // can't improve enough — skip
        const h2=h/2;
        _push(_cell(cx-h2,cy-h2,h2)); _push(_cell(cx+h2,cy-h2,h2));
        _push(_cell(cx-h2,cy+h2,h2)); _push(_cell(cx+h2,cy+h2,h2));
    }
    return [bx,by];
}
// Signed distance from point to polygon: positive = inside, negative = outside.
function _fpDist(px,py,pts) {
    let inside=false, minD=Infinity;
    for (let i=0,j=pts.length-1; i<pts.length; j=i++) {
        const [xi,yi]=pts[i],[xj,yj]=pts[j];
        if ((yi>py)!==(yj>py) && px<(xj-xi)*(py-yi)/(yj-yi)+xi) inside=!inside;
        const dx=xj-xi,dy=yj-yi;
        const t=Math.max(0,Math.min(1,((px-xi)*dx+(py-yi)*dy)/(dx*dx+dy*dy||1)));
        const ex=xi+t*dx-px,ey=yi+t*dy-py;
        minD=Math.min(minD,ex*ex+ey*ey);
    }
    return (inside?1:-1)*Math.sqrt(minD);
}
function _fpAbbrev(name) {
    if (!name) return '';
    const w = name.trim().split(/\s+/);
    return w.length === 1 ? name.substring(0,4).toUpperCase() : w.map(x=>x[0].toUpperCase()).join('').substring(0,5);
}

window.FloorPlanPicker = FloorPlanPicker;
})();
}
</script>
