/**
 * counters.js: Represents a single military counter, with functions to convert to HTML.
 */

const HOPLITE = "hoplite";
const TRIREME = "trireme";

const MIL_DIM = {
    "l": 100,
    "s": 62
}


// match MAIN/ALLY ATT/DEF constants in php
const BATTLE_POS = {
    1: "att",
    2: "att_ally",
    3: "def",
    4: "def_ally"
}

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.counter", null, {

        /**
         * 
         * @param {string} city 
         * @param {string} type 
         * @param {string} strength 
         * @param {string} id 
         * @param {string} location
         * @param {int} position 
         */
        constructor: function(city, type, strength, id, location="", position=0) {
            this.city = city;
            this.type = type;
            this.strength = strength;
            this.id = id;
            this.location = location;
            this.position = position;
        },

        getCity: function() {
            return this.city;
        },

        getType: function() {
            return this.type;
        },

        getStrength: function() {
            return this.strength;
        },

        getId: function() {
            return this.id;
        },

        getLocation: function() {
            return this.location;
        },

        getPosition: function() {
            return this.position;
        },

        getBattlePosition: function() {
            return BATTLE_POS[this.position];
        },

        /**
         * 
         * @param {string} newId 
         */
        setId: function(newId) {
            this.id = newId;
        },

        /**
         * Create html div for a military counter.
         * @param {int} margin
         * @param {int} top
         * @param {boolean} rel make display relative?
         * @returns html div for a military counter
         */
        toDiv: function(margin, top, rel=false) {
            const counter_id = this.city+"_"+this.type+"_"+this.strength+"_"+this.id;
            const class_id = "prk_military prk_"+this.type;
            const [xoff, yoff] = this.getOffsets();
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
         toRelativeDiv: function() {
            return this.toDiv(5, 0, true);
        },

        /**
         * Creates a military counter at a battle slot
         * @param {int} ct stack count
         * @returns html div for a military counter
         */
        toBattleDiv: function(ct) {
            const counter_id = this.city+"_"+this.type+"_"+this.strength+"_"+this.id;
            const class_id = "prk_military prk_"+this.type+" prk_"+this.type+"_battle prk_at_battle";
            const [xoff, yoff] = this.getOffsets();
            const style = "background-position: "+xoff+"px "+yoff+"px; margin-left: "+(8*ct)+"px; top: 0px;";
            const html = '<div id=\"'+counter_id+'"\" class=\"'+class_id+'"\" style=\"'+style+'"></div>';
            return html;
        },

        /**
         * Create array[2] with background-position offsets for a military counter.
         * @param returns [x,y] values
         */
         getOffsets: function() {
            var xdim, ydim;
            if (this.type == HOPLITE) {
                xdim = MIL_DIM.s;
                ydim = MIL_DIM.l;
            } else if (this.type == TRIREME) {
                xdim = MIL_DIM.l;
                ydim = MIL_DIM.s;
            } else {
                throw Error("invalid unit type: "+ this.type);
            }
            const xoff = -1 * this.strength * xdim;
            const yoff = -1 * MILITARY_ROW[this.city] * ydim;
            return [xoff,yoff];
        },

        /**
         * Copy a military counter as a dialog icon from an existing one
         * @param {DOMElement} counter 
         * @returns relative div
         */
         copy: function() {
            const ctry = new perikles.counter(this.city, this.type, this.strength, this.id+"_copy");
            const counter_html = ctry.toRelativeDiv();
            return counter_html;
        },

        /**
         * Add this unit to its city stack.
         */
         addToStack: function() {
            const stack = $(this.city+"_military");
            const ct = stack.childElementCount;
            const top = (this.type == TRIREME) ? MIL_DIM.s : 0;
            const counter = this.toDiv(2*ct, top);
            dojo.place(counter, stack);
        },

    })
});