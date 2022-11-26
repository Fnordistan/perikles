/**
 * decorator.js: factory to generate customized HMTL for various components.
 * 
 */

define(["dojo/_base/declare"], function (declare) {

    // perikles-specific
    const PLAYER_COLORS = {
        "E53738" : "red",
        "37BC4C" : "green",
        "39364F" : "black",
        "E5A137" : "orange",
        "FFF" : "white",
    }

    // map the default colors to colorblind equivalent
    const COLORBLIND_COLORS = {
        "red" : "D55E00",
        "green" : "009E73",
        "black" : "000",
        "orange" : "F0E442",
        "white" : "FFF",
    }

    return declare("perikles.decorator", null, {
        
        /**
         * Initialize with the game's players.
         */
        constructor: function (players) {
            this.players = players;
        },

        /**
         * Change the title banner.
         * @param {string} text 
         */
         setMainTitle : function(text) {
            $('pagemaintitletext').innerHTML = text;
        },


        /**
         * Put a new style element into an HTML div's style attribute
         * @param {string} html 
         * @param {string} style 
         * @returns 
         */
         prependStyle: function(html, style) {
            html = html.replace('style="', 'style="'+style+';');
            return html;
        },

        /**
         * From BGA Cookbook. Return "You" in this player's color
         * @param {int} player_id
         * @param {bool} cb colorblind option
         */
         spanYou: function(player_id, cb=false) {
            const player = this.players[player_id]; 
            let color = player.color;
            if (cb) {
                color = this.toColorBlind(color);
            }
            const color_bg = this.colorBg(player);
            const you = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + __("lang_mainsite", "You") + "</span>";
            return you;
        },

        /**
         * Create span with player's name in color.
         * @param {int} player_id
         * @param {bool} cb colorblind option
         */
         spanPlayerName: function(player_id, cb=false) {
            const player = this.players[player_id];
            const color_bg = this.colorBg(player);
            let color = player.color;
            if (cb) {
                color = this.toColorBlind(color);
            }
            const pname = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + player.name + "</span>";
            return pname;
        },

        /**
         * Customized player colors per player_id
         * @param {string} player_id 
         * @return color as name, or null if checked for a non-player
         */
         playerColor: function(player_id) {
            let color = null;
            const player = this.players[player_id];
            if (player) {
                color = PLAYER_COLORS[player.color];
            }
            return color;
        },

        /**
         * Expects one of the player color hex strings and returns colorblind alternative.
         * @param {string} player_color value of player.color
         * @returns colorblind equivalent as hex string
         */
        toColorBlind: function(player_color) {
            const color = PLAYER_COLORS[player_color];
            return COLORBLIND_COLORS[color];
        },

        /**
         * Get the style tag for background-color for a player name (shadow for white text)
         * @param {Object} player 
         * @returns css tag or empty string
         */
         colorBg: function(player) {
            let color_bg = "";
            if (player.color == "FFF") {
                color_bg = WHITE_OUTLINE;
            }
            return color_bg;
        },

        /**
         * This method will remove all inline style added to element that affect positioning
         */
         stripPosition: function (token) {
            token = $(token);

            token.style.removeProperty("display");
            token.style.removeProperty("top");
            token.style.removeProperty("bottom");
            token.style.removeProperty("left");
            token.style.removeProperty("right");
            token.style.removeProperty("position");
        },

        /**
         * For elements that have been assigned opacity 0 for some reason.
         * @param {DOMElement} token 
         */
        visibilize: function(token) {
            token.style['opacity'] = 'initial';
        },


        /**
         * Strip all elements of the document of a given class name
         * @param {string} cls className
         */
         stripClassName: function(cls) {
            const actdiv = document.getElementsByClassName(cls);
            [...actdiv].forEach( a => a.classList.remove(cls));
        },

        /**
         * Add highlight data tag to an element.
         * @param {DOM} element 
         */
        highlight: function(element) {
            element.dataset.highlight = "true";
        },

        /**
         * Remove the highlight data tag from an element.
         * @param {DOM} element 
         */
        unhighlight: function(element) {
            delete element.dataset.highlight;
        },

        /**
         * Check whether an element is highlighted.
         * @param {DOM} element 
         * @returns true if element is highlighted
         */
         isHighlighted: function(element) {
            return element.dataset.highlight == "true";
        },

        /**
         * Removes active from all elements.
         */
        removeAllHighlighted: function() {
            const hilighted = document.querySelectorAll('[data-highlight="true"]');
            hilighted.forEach(a => {
                this.unhighlight(a);
            });
        }

    })
});