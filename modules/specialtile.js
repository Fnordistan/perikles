/**
 * specialtile.js
 * For Special Tiles.
 */

const SPECIAL_TILE_SCALE = 0.2;

const PERIKLES = "perikles";
const PERSIANFLEET = "persianfleet";
const SLAVEREVOLT = "slaverevolt";
const BRASIDAS = "brasidas";
const THESSALANIANALLIES = "thessalanianallies";
const ALKIBIADES = "alkibiades";
const PHORMIO = "phormio";
const PLAGUE = "plague";

const CARDS = [PERIKLES, PERSIANFLEET, SLAVEREVOLT, BRASIDAS, THESSALANIANALLIES, ALKIBIADES, PHORMIO, PLAGUE];

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.specialtile", null, {

        /**
         * Create a specialtile component.
         * @param {string} player_id owner
         * @param {string} spec label (null if facedown)
         * @param {bool} bUsed 
         */
        constructor: function(player_id, spec, bUsed) {
            this.id = player_id;
            this.special = spec;
            this.used = bUsed;
        },

        /**
         * Lazy-initialize translateable strings for card texts.
         * @param {string} label name of card
         * @param {string} att must be "title" or "description"
         * @returns 
         */
        getCardAttribute: function(label, att) {
            // sanity checks
            if (label == null) {
                throw new Error("Cannot get "+att+" for facedown card");
            } else if (!CARDS.includes(label)) {
                throw new Error("invalid card name: "+label);
            }
            if (!(att == "title" || att == "description")) {
                throw new Error("invalid attribute: "+att);
            }

            const text = {
                "perikles" : { title : _("PERIKLES"), description: _("Place two Influence cubes in Athens. This tile can be played when it is your turn to select an Influence tile, either just before or just after taking the tile.")},
                "persianfleet" : { title : _("PERSIAN FLEET"), description: _("This tile can be played just before a trireme battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat.")},
                "slaverevolt" : { title : _("SLAVE REVOLT"), description: _("This tile can be played when it is your turn to commit forces to a location. Take one Spartan hoplite counter, either from the board or from the controlling player, and place it back in Sparta. That counter cannot be involved in combat this turn. You cannot examine the counter you remove. (It is selected randomly.) The counter will come back into play in the next turn.")},
                "brasidas" : { title : _("BRASIDAS"), description: _("This tile can be played just before a hoplite battle is about to be resolved. All Spartan hoplite counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled.")},
                "thessalanianallies" : { title : _("THESSALANIAN ALLIES"), description: _("This tile can be played just before a hoplite battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat.")},
                "alkibiades" : { title : _("ALKIBIADES"), description: _("Player can take two Influence cubes of any color from any city/cities and move them to any city of their choice. These cubes may not be moved from a candidate space, nor may they be moved to one.")},
                "phormio" : { title : _("PHORMIO"), description: _("This tile can be played just before a trireme battle is about to be resolved. All Athenian trireme counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled.")},
                "plague" : { title : _("PLAGUE"), description: _("This tile can be played during the Influence Tile phase. Select one city. All players remove half (rounded down) of their Influence cubes from that city.")} 
            };
            return text[label][att];
        },

        /**
         * Returns true if this specialtile has been used.
         * @returns {bool}
         */
        isUsed() {
            return this.used;
        },
    
        /**
         * Returns true if this specialtile is to be displayed face up.
         * @returns {bool}
         */
        isFaceup() {
            return (this.special != null);
        },

        /**
         * Generate the HTML for a card, front or back depending on whether special=0.
         * @returns {html} div 
         */
        getDiv: function() {
            const html = this.isFaceup() ? this.frontDiv() : this.backDiv();
            return html;
        },

        /**
         * Show the front of a card.
         * @returns {html} div 
         */
        frontDiv: function() {
            let classes = "prk_special_tile "+this.special;
            let dataset = "";
            if (this.used) {
                dataset = 'data-status = "used"';
            }
            const html = '<div id="'+this.special+'_special_tile" class="'+classes+'" '+dataset+' data-side="front" style="--scale: '+SPECIAL_TILE_SCALE+';"></div>';
            return html;
        },

        /**
         * Show the back of a card.
         * @returns {html} div 
         */
        backDiv: function() {
            const html = '<div id="special_'+this.id+'" class="prk_special_tile" data-side="back" style="--scale: '+SPECIAL_TILE_SCALE+';"></div>';
            return html;
        },

        /**
         * Get translateable all CAPS Title string.
         * @returns {string} translated name
         */
        getTitle: function() {
            return this.getCardAttribute(this.special, "title");
        },

        /**
         * Get translateable description.
         * @returns {string} translated description string
         */
        getDescription: function() {
            return this.getCardAttribute(this.special, "description");
        },

        /**
         * HTML for Special tile tooltip.
         * @param {string} tilenum 
         * @returns html
         */
         createSpecialTileTooltip: function() {
            const tt = '<div id="'+this.special+'_special_tt" style="display: flex; flex-direction: row;">\
                                <div style="flex: 1;">\
                                <h1 style="font-family: Bodoni Moda;">'+this.getTitle()+'</h1>\
                                <hr\>\
                                '+this.getDescription()+'\
                            </div>\
                            <div class="prk_special_tile_tt '+this.special+'" style="--scale: 0.5;"></div>\
                        </div>';
            return tt;
        },

    })
});