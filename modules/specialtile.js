/**
 * specialtile.js
 * For special tiles.
 */

const SPECIAL_TILE_SCALE = 0.2;
// correspond to materials array
const SPECIAL_TILES = ['perikles', 'persianfleet', 'slaverevolt', 'brasidas', 'thessalanianallies', 'alkibiades', 'phormio', 'plague'];
const SPECIAL_MARGIN = "2px";

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.specialtile", null, {

        constructor: function(player_id, s_ix, bUsed) {
            this.id = player_id;
            this.special = s_ix;
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
            return (this.special != 0);
        },

        /**
         * Generate the HTML for a card, front or back depending on whether special=0.
         * @returns {html} div 
         */
        getDiv: function() {
            let html = "";
            if (this.special == 0) {
                html = this.backDiv();
            } else {
                html = this.frontDiv();
            }
            return html;
        },

        /**
         * Show the front of a card.
         * @returns {html} div 
         */
        frontDiv: function() {
            const specname = SPECIAL_TILES[Math.abs(this.special)-1];
            let classes = "prk_special_tile prk_special_tile_front "+specname;
            if (this.used) {
                classes += " prk_special_tile_used";
            }
            const html = '<div id="'+specname+'_special_tile" class="'+classes+'" style="--scale: '+SPECIAL_TILE_SCALE+'; margin: '+SPECIAL_MARGIN+';"></div>';
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
         * HTML for Special tile tooltip.
         * @param {string} tilenum 
         * @returns html
         */
         createSpecialTileTooltip: function() {
            const TITLES = [
                _("PERIKLES"),
                _("PERSIAN FLEET"),
                _("SLAVE REVOLT"),
                _("BRASIDAS"),
                _("THESSALANIAN ALLIES"),
                _("ALKIBIADES"),
                _("PHORMIO"),
                _("PLAGUE")
            ];
            const DESC = [
                _("Place two Influence cubes in Athens. This tile can be played when it is your turn to select an Influence tile, either just before or just after taking the tile."),
                _("This tile can be played just before a trireme battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                _("This tile can be played when it is your turn to commit forces to a location. Take one Spartan hoplite counter, either from the board or from the controlling player, and place it back in Sparta. That counter cannot be involved in combat this turn. You cannot examine the counter you remove. (It is selected randomly.) The counter will come back into play in the next turn."),
                _("This tile can be played just before a hoplite battle is about to be resolved. All Spartan hoplite counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                _("This tile can be played just before a hoplite battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                _("Player can take two Influence cubes of any color from any city/cities and move them to any city of their choice. These cubes may not be moved from a candidate space, nor may they be moved to one."),
                _("This tile can be played just before a trireme battle is about to be resolved. All Athenian trireme counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                _("This tile can be played during the Influence Tile phase. Select one city. All players remove half (rounded down) of their Influence cubes from that city.")
            ];

            const title = TITLES[Math.abs(this.special)-1];
            const text = DESC[Math.abs(this.special)-1];
            const specname = SPECIAL_TILES[Math.abs(this.special)-1];
            const tt = '<div id="'+specname+'_special_tt" style="display: flex; flex-direction: row;">\
                                <div style="flex: 1;">\
                                <h1 style="font-family: Bodoni Moda;">'+title+'</h1>\
                                <hr\>\
                                '+text+'\
                            </div>\
                            <div class="prk_special_tile_tt '+specname+'" style="--scale: 0.5;"></div>\
                        </div>';
            return tt;
        },

    })
});