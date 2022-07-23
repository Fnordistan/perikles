/**
 * specialtile.js
 * For special tiles.
 */

const SPECIAL_TILE_SCALE = 0.2;
const SPECIAL_MARGIN = "2px";

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.specialtile", null, {

        /**
         * Create a SpecialTile Component
         * @param {string} player_id 
         * @param {string} spec null if facedown
         * @param {bool} bUsed 
         */
        constructor: function(player_id, spec, bUsed) {
            this.id = player_id;
            this.special = spec;
            this.used = bUsed;
        },

        /**
         * Returns true if this specialtile has been used.
         * @returns {boolean}
         */
        isUsed() {
            return this.used;
        },
    
        /**
         * Returns true if this specialtile is to be displayed face up.
         * @returns {boolean}
         */
        isFaceup() {
            return (this.special != null);
        },

        /**
         * Generate the HTML for a card, front or back depending on whether special=0.
         * @returns {html} div 
         */
        getDiv: function() {
            let html = "";
            if (this.special) {
                html = this.frontDiv();
            } else {
                html = this.backDiv();
            }
            return html;
        },

        /**
         * Show the front of a card.
         * @returns {html} div 
         */
        frontDiv: function() {
            let classes = "prk_special_tile prk_special_tile_front "+this.special;
            if (this.used) {
                classes += " prk_special_tile_used";
            }
            const html = '<div id="'+this.special+'_special_tile" class="'+classes+'" style="--scale: '+SPECIAL_TILE_SCALE+'; margin: '+SPECIAL_MARGIN+';"></div>';
            return html;
        },

        /**
         * Show the back of a card.
         * @returns {html} div 
         */
        backDiv: function() {
            const html = '<div id="special_'+this.id+'" class="prk_special_tile prk_special_tile_back" style="--scale: '+SPECIAL_TILE_SCALE+';"></div>';
            return html;
        },

        /**
         * Get translateable all CAPS Titile string.
         * @returns {string} translated name
         */
        getTitle: function() {
            const TITLES = {
                "perikles" : _("PERIKLES"),
                "persianfleet" : _("PERSIAN FLEET"),
                "slaverevolt" : _("SLAVE REVOLT"),
                "brasidas" : _("BRASIDAS"),
                "thessalanianallies" : _("THESSALANIAN ALLIES"),
                "alkibiades" : _("ALKIBIADES"),
                "phormio" : _("PHORMIO"),
                "plagues" : _("PLAGUE")
            };
            return TITLES[this.special];
        },

        /**
         * Get translateable description.
         * @returns {string} translated description string
         */
        getDescription: function() {
            const DESC = {
                "perikles" : _("Place two Influence cubes in Athens. This tile can be played when it is your turn to select an Influence tile, either just before or just after taking the tile."),
                "persianfleet" : _("This tile can be played just before a trireme battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                "slaverevolt" : _("This tile can be played when it is your turn to commit forces to a location. Take one Spartan hoplite counter, either from the board or from the controlling player, and place it back in Sparta. That counter cannot be involved in combat this turn. You cannot examine the counter you remove. (It is selected randomly.) The counter will come back into play in the next turn."),
                "brasidas" : _("This tile can be played just before a hoplite battle is about to be resolved. All Spartan hoplite counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                "thessalanianallies" : _("This tile can be played just before a hoplite battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                "alkibiades" : _("Player can take two Influence cubes of any color from any city/cities and move them to any city of their choice. These cubes may not be moved from a candidate space, nor may they be moved to one."),
                "phormio" : _("This tile can be played just before a trireme battle is about to be resolved. All Athenian trireme counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                "plagues" : _("This tile can be played during the Influence Tile phase. Select one city. All players remove half (rounded down) of their Influence cubes from that city.")
            };
            return DESC[this.special];
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