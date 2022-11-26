/**
 * stack.js:
 * Represents military stacks.
 */

define(["dojo/_base/declare"], function (declare) {

    const STARTING_COUNTERS = {
        "persia": {"h2" : 2, "h3" : 4, "t2" : 2, "t3" : 2},
        "athens": {"h1" : 2, "h2" : 2, "h3" : 2, "t1" : 2, "t2" : 2, "t3" : 2, "t4" : 2},
        "sparta": {"h1" : 2, "h2" : 3, "h3" : 3, "h4" : 2, "t1" : 1, "t2" : 2, "t3" : 1},
        "argos": {"h1" : 2, "h2" : 2, "h3" : 2, "t1" : 1, "t2" : 1, "t3" : 1},
        "corinth": {"h1" : 1, "h2" : 3, "h3" : 1, "t1" : 2, "t2" : 2, "t3" : 1},
        "thebes": {"h1" : 2, "h2" : 3, "h3" : 2, "t1" : 1, "t2" : 1},
        "megara": {"h1" : 1, "h2" : 1, "t1" : 1, "t2" : 1, "t3" : 1}
    };
    
    return declare("perikles.stack", null, {
        
        /**
         * Initialize stack trackers.
         */
        constructor: function () {
            this.military_zones = {};
            this.stack_listeners = {};
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
         * @param {bool} bSpread 
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
         * Spread out all Hoplite and Trireme counters in a city stack
         * @param {Element} city_mil the city stack element
         */
         spreadMilitaryUnits: function(city_mil) {
            const hoplites = city_mil.getElementsByClassName("prk_hoplite");
            const triremes = city_mil.getElementsByClassName("prk_trireme");
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
         * Connected to military counters at battles.
         * @param {Event} evt 
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
                // u.style['outline'] = "solid white 3px";
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
         * Sort the military stack.
         * @param {string} zone
         * @param {bool} stack (optional, default true) false for deadpool sorting
         */
        sortStack: function(zone, isstack=true) {
            const stack = $(zone);
            const counters = stack.getElementsByClassName("prk_military");
            const sorted_counters = this.sorted_counters(counters);
            while (stack.firstChild) {
                stack.removeChild(stack.firstChild);
            }
            for (let i = 0; i < sorted_counters.length; i++) {
                const counter = sorted_counters[i];
                if (isstack) {
                    counter.style['margin'] = (i*2)+"px";
                }
                dojo.place(counter, stack);
            }
        },

        /**
         * Take a mixed batch of counters and sort them by city, unit, strength, etc.
         * Sorts by ids, which should always be ascending relative to stacks.
         * @param {element list} counters 
         * @returns sorted array
         */
        sorted_counters: function(counters) {
            const sortbyunit = [...counters].sort((a,b) => {
                const a_id = a.id.split("_")[3];
                const b_id = b.id.split("_")[3];
                return a_id - b_id;
            });
            return sortbyunit;
        },

        /**
         * For tooltip over an empty stack.
         * @param {string} city
         * @param {string} city_span html div
         * @return {string} HTML
         */
        showStartingForces: function(city, city_span) {
            const startcounters = STARTING_COUNTERS[city];
            // this will be replaced by the calling function
            let html = '<div class="prk_citystack_tooltip">';
            html += city_span;
            html += '<h3>'+_("Starting forces")+'</h3>';
            html += '<div class="prk_citystack_tooltip_inner">';
            let trireme_col = '<div class="prk_stack_column">';
            let hoplite_col = '<div class="prk_stack_column">';
            for (let [unit, num] of Object.entries(startcounters)) {
                const type = (unit[0] == "h") ? HOPLITE : TRIREME;
                const vpad = (type == HOPLITE) ? '1em' : '0.5em';
                const strength = unit[1];
                const counter = new perikles.counter(city, type, strength, "startingtt");
                const counter_row = '<div style="display: flex; flex-direction: row;">'+counter.toLogIcon(true)+'<span style="padding: '+vpad+' 2px;"> &times; '+num+'</span></div>';
                if (type == HOPLITE) {
                    hoplite_col += counter_row;
                } else {
                    trireme_col += counter_row;
                }
            }
            trireme_col += '</div>';
            hoplite_col += '</div>';
            html += hoplite_col + trireme_col;
            html += '</div></div>';
            return html;
        },

    })
});