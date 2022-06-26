/**
 * counters.js: Perikles military counter contruction and event handling.
 */

const HOPLITE = "hoplite";
const TRIREME = "trireme";

const MIL_DIM = {
    "l": 100,
    "s": 62
}

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.counter", null, {
        
        /**
         * Initialize stack trackers.
         */
        constructor: function () {
            this.military_zones = {};
        },

        /**
         * Adds a stack with a boolean to spread or not.
         * Initializes with spread=false by default.
         * @param {string} zone 
         */
        addStack: function(zone) {
            this.military_zones[zone] = false;
        },

        /**
         * Set whether spread should be enabled.
         * @param {string} zone 
         * @param {boolean} bSpread 
         */
        enableSpread: function(zone, bSpread) {
            if (zone in this.military_zones) {
                this.military_zones[zone] = bSpread;
            } else {
                throw new Error("Invalid Stack "+zone);
            }
        },

        /**
         * Is this stack enabled to spread?
         * @param {string} zone 
         * @returns true if spread is enabled for this stack
         */
        spreadable: function(zone) {
            if (zone in this.military_zones) {
                return this.military_zones[zone];
            } else {
                throw new Error("Invalid Stack "+zone);
            }
        },

        /**
         * Unspread military units.
         * @param {Object} city_mil 
         */
        unspread: function(zone) {
            const stack = $(zone);
            for (const mil of stack.children) {
                Object.assign(mil.style, {'transform' : null, 'z-index': null});
            }
            this.enableSpread(zone, false);
        },

        /**
         * Create html div for a military counter.
         * @param {string} city 
         * @param {string} type HOPLITE or TRIREME
         * @param {string} strength 
         * @param {string} id tag at end of id string
         * @param {int} margin
         * @param {int} top
         * @param {boolean} rel make display relative?
         * @returns html div for a military counter
         */
        createCounter: function(city, type, strength, id, margin, top, rel=false) {
            const counter_id = city+"_"+type+"_"+strength+"_"+id;
            const class_id = "prk_military prk_"+type;
            const [xoff, yoff] = this.getOffsets(city, strength, type);
            let style = "background-position: "+xoff+"px "+yoff+"px; margin: "+margin+"px; top: "+top+"px";
            if (rel) {
                style += "; position: relative";
            }
            style += ";";
            const html = '<div id=\"'+counter_id+'"\" class=\"'+class_id+'"\" style=\"'+style+'"></div>';
            return html;
        },

        /**
         * Creates a military counter with position: relative.
         * @param {string} city 
         * @param {string} type HOPLITE or TRIREME
         * @param {string} strength 
         * @param {string} id tag at end of id string
         * @returns html div for a military counter
         */
         createCounterRelative: function(city, type, strength, id) {
            return this.createCounter(city, type, strength, id, 5, 0, true);
        },

        /**
         * Create array[2] with background-position offsets for a military counter.
         * @param {*} city 
         * @param {*} strength 
         * @param {*} unit
         * @param returns [x,y] values
         */
         getOffsets: function(city, strength, unit) {
            var xdim, ydim;
            if (unit == HOPLITE) {
                xdim = MIL_DIM.s;
                ydim = MIL_DIM.l;
            } else if (unit == TRIREME) {
                xdim = MIL_DIM.l;
                ydim = MIL_DIM.s;
            } else {
                throw Error("invalid unit type: "+ unit);
            }
            const xoff = -1 * strength * xdim;
            const yoff = -1 * MILITARY_ROW[city] * ydim;
            return [xoff,yoff];
        },

        /**
         * Copy a military counter as a dialog icon from an existing one
         * @param {DOMElement} counter 
         * @returns relative div
         */
         copy: function(counter) {
            const [city,unit,strength,id] = counter.id.split('_');
            const counter_html = this.createCounterRelative(city, unit, strength, id+"_copy");
            return counter_html;
        },

        /**
         * Connected to military counters at battles.
         * @param {*} evt 
         */
         splayUnits: function(evt) {
            const units = evt.currentTarget.getElementsByClassName("prk_at_battle");
            let i = 0;
            [...units].forEach(u => {
                if (u.classList.contains("prk_hoplite")) {
                    let hoffset = 8+(i*50);
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, "+hoffset+", -20)";
                } else {
                    let toffset = -8+(i*80);
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, "+toffset+", -4)";
                }
                u.style['outline'] = "solid white 3px";
                u.style['z-index'] = "99";
                i++;
            });
        },

        /**
         * Connected to military counters at battles.
         * @param {Event} evt 
         */
        unsplayUnits: function(evt) {
            const units = evt.currentTarget.getElementsByClassName("prk_at_battle");
            [...units].forEach(u => {
                if (u.classList.contains("prk_hoplite")) {
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, 8, -20) rotate(90deg)";
                } else {
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, -8, -4)";
                }
                Object.assign(u.style, {
                    'outline': null,
                    'z-index': null
                });
            });
        },

        /**
         * When hovering over a military counter.
         * @param {Event} evt 
         */
         hoverUnit: function(evt) {
            evt.currentTarget.classList.add("prk_military_active");
        },

        /**
         * When unhovering a military counter.
         * @param {Event} evt 
         */
        unhoverUnit: function(evt) {
            evt.currentTarget.classList.remove("prk_military_active");
        },

        /**
         * Add stack for a civ/Persia military.
         * Make military display available counters
         */
         decorateMilitaryStack: function(city_mil_id) {
            this.addStack(city_mil_id);
            const city_mil = $(city_mil_id);
            city_mil.addEventListener('click', () => {
                if (this.spreadable(city_mil_id)) {
                    this.unspread(city_mil_id);
                } else {
                    this.spreadMilitaryUnits(city_mil);
                }
            });

            city_mil.addEventListener('mouseleave', () => {
                this.unspread(city_mil_id);
            });
        },

        /**
         * Spread out all Hoplite and Trireme counters
         */
         spreadMilitaryUnits: function(city_mil) {
            const hoplites = [];
            const triremes = [];
            for (const mil of city_mil.children) {
                if (mil.classList.contains("prk_hoplite")) {
                    hoplites.push(mil.id);
                } else if  (mil.classList.contains("prk_trireme")) {
                    triremes.push(mil.id);
                }
            }
            let n = 0;
            // Athens spreads to left
            let athens_off = 0;
            if (city_mil.id == "athens_military") {
                athens_off = -1 * Math.max((hoplites.length * MIL_DIM.s), (triremes.length * MIL_DIM.l));
            }
            for (hop of hoplites) {
                let xoff = athens_off+(n*MIL_DIM.s);
                let yoff = n*-2;
                Object.assign($(hop).style, {'transform' : "translate("+xoff+"px,"+yoff+"px)", 'z-index': 1});
                n++;
            }
            const rec = city_mil.getBoundingClientRect();
            n = 0;
            for (tri of triremes) {
                let tridim = $(tri).getBoundingClientRect();
                let xoff = (-2 * hoplites.length) + athens_off+(n*MIL_DIM.l);
                let yoff = 22 + rec.bottom - tridim.top;
                Object.assign($(tri).style, {'transform' : "translate("+xoff+"px,"+yoff+"px)", 'z-index': 1});
                n++;
            }
            this.enableSpread(city_mil.id, true);
        },

    })
});