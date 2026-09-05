/**
 * counters.js: Represents a single military counter, with functions to convert to HTML.
 */

 const HOPLITE = "hoplite";
 const TRIREME = "trireme";

// counter pixel dimensions on long and short sides
const MIL_DIM = {
    "l": 100,
    "s": 62
}

define(["dojo/_base/declare"], function (declare) {

    // rows where counters are found on the sprite image
    const MILITARY_ROW = {'argos': 0, 'athens': 1, 'corinth': 2, 'megara': 3, 'sparta': 4, 'thebes': 5, 'persia': 6};

    // match MAIN/ALLY ATT/DEF constants in php
    const BATTLE_POS = {
        1: "att",
        2: "att_ally",
        3: "def",
        4: "def_ally"
    }

    return declare("perikles.counter", null, {

        /**
         * Constructor for a military counter.
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
         * Id used for div. Should always be: "city"_"type"_"strength"_"id"
         * @returns string
         */
        getCounterId: function() {
            const counter_id = [this.city, this.type, this.strength, this.id].join("_");
            return counter_id;
        },

        /**
         * Create html div for a military counter.
         * @param {int} margin
         * @param {int} top
         * @param {bool} (optional) rel make display relative?
         * @param {string} (optional) display 
         * @returns html div for a military counter
         */
        toDiv: function(margin, top, rel=false, display=null) {
            const counter_id = this.getCounterId();
            const class_id = "prk_military prk_"+this.type;
            const [xoff, yoff] = this.getOffsets();
            let style = "background-position: "+xoff+"px "+yoff+"px; margin: "+margin+"px; top: "+top+"px";
            if (rel) {
                style += "; position: relative";
            }
            if (display) {
                style += "; display: "+display;
            }
            style += ";";
            const html = '<div id=\"'+counter_id+'"\" class=\"'+class_id+'"\" style=\"'+style+'"></div>';
            return html;
        },

        /**
         * Create html div for a unit in logs.
         * @returns html for a log message
         */
        toLogIcon: function(instack=false) {
            const counter_id = this.getCounterId()+"_log";
            const class_id = "prk_military prk_"+this.type;
            let [xoff, yoff] = this.getOffsets();
            if (instack) {
                xoff *= 0.5;
                yoff *= 0.5;
            }
            let style = "background-position: "+xoff+"px "+yoff+"px; position: relative;";
            const html = '<div id="'+counter_id+'" class="'+class_id+'" style="'+style+';" data-stack="'+instack+'"></div>';
            return html;
        },

        /**
         * Creates a military counter with position: relative.
         * @param {string} (optional) display
         * @returns html div for a military counter
         */
         toRelativeDiv: function(display=null) {
            return this.toDiv(5, 0, true, display);
        },

        /**
         * Creates a military counter at a battle slot
         * @param {int} ct stack count
         * @returns html div for a military counter
         */
        toBattleDiv: function(ct) {
            const counter_id = this.getCounterId();
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
            const top = (this.type == TRIREME) ? MIL_DIM.s : 0;
            const newDiv = this.toDiv(stack.children.length*2, top);
            dojo.place(newDiv, stack);
        },

        /**
         * Assumes this counter has been set a location at a battle tile.
         * Place a military counter on the battle stack at the location tile.
         * @param {string} slot (optional) battle slot; worked out from the location tile if not given
         * @returns {DOM} the counter placed, or null if there was nowhere to put it
         */
         placeBattle: function(slot=null) {
            const location = this.getLocation();
            if (slot == null) {
                // the location tile sits inside the battle zone for its slot
                const tile = $(location+"_tile");
                const battle_zone = tile ? tile.closest("[id^='battle_zone_']") : null;
                if (battle_zone == null) {
                    console.warn("placeBattle: "+location+" is not in a battle zone");
                    return null;
                }
                slot = battle_zone.id.substring("battle_zone_".length);
            }
            const battlepos = this.getBattlePosition();
            if (battlepos == undefined) {
                console.warn("placeBattle: "+this.getCounterId()+" at "+location+" has no battle position ("+this.getPosition()+")");
                return null;
            }
            const place = ["battle", slot, this.getType(), battlepos].join("_");
            const stack = $(place);
            if (stack == null) {
                console.warn("placeBattle: no battle stack "+place+" for "+this.getCounterId());
                return null;
            }
            const stackct = stack.childElementCount;
            // zero ids for face-down units
            if (this.getStrength() == 0) {
                this.setId(stackct+"_"+location);
            }
            const battlecounter = this.toBattleDiv(stackct);
            return dojo.place(battlecounter, stack);
        },

        /**
         * Place an HTML of this counter in the appropriate place in DEADPOOL or player military board
         * @param {string} target DEAD_POOL or a player_id
         * @return {DOM} counter object
         */
        placeCounterInContainer: function(target) {
            if (target == DEAD_POOL) {
                // make sure it's visible
                $(DEAD_POOL).style['display'] = 'block';
            }
            const counter_div = this.toDiv(1, 0);
            const target_container = [this.city, this.type, this.strength, target].join("_");
            const counterObj = dojo.place(counter_div, $(target_container));
            const bottomCounters = $(target_container).childElementCount-1;
            Object.assign(counterObj.style, {margin: (bottomCounters*4)+"px"});
            if (target == DEAD_POOL) {
                counterObj.dataset.deadpool = "true";
            }
            Object.assign($(target_container).style, {display: "block", 'margin-bottom': (bottomCounters*4)+"px"});
            return counterObj;
        },

    })
});