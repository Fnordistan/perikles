/**
 * stack.js:
 * Represents military stacks.
 */

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.stack", null, {
        
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
         * Sort the military stack on a city.
         * @param {string} city 
         */
        sortStack: function(city) {
            const stack = $(city+"_military");
            const counters = stack.children;
            const sorted_counters = this.sorted_counters(counters);
            while (stack.firstChild) {
                stack.removeChild(stack.firstChild);
            }
            for (let i = 0; i < sorted_counters.length; i++) {
                const counter = sorted_counters[i];
                counter.style['margin'] = (i*2)+"px";
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

    })
});