/**
 * decorator.js: factory to generate customized HMTL for various components.
 * 
 */

// perikles-specific
const PLAYER_COLORS = {
    "E53738" : "red",
    "37BC4C" : "green",
    "39364F" : "black",
    "E5A137" : "orange",
    "FFF" : "white",
}

define(["dojo/_base/declare"], function (declare) {
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
         */
         spanYou: function(player_id) {
            const player = this.players[player_id]; 
            const color = player.color;
            const color_bg = this.colorBg(player);
            const you = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + __("lang_mainsite", "You") + "</span>";
            return you;
        },

        /**
         * Create span with player's name in color.
         * @param {int} player 
         */
         spanPlayerName: function(player_id) {
            const player = this.players[player_id];
            const color_bg = this.colorBg(player);
            const pname = "<span style=\"font-weight:bold;color:#" + player.color + ";" + color_bg + "\">" + player.name + "</span>";
            return pname;
        },

        /**
         * Customized player colors per player_id
         * @param {string} player_id 
         */
         playerColor: function(player_id) {
            const player = this.players[player_id];
            const color = player.color;
            return PLAYER_COLORS[color];
        },

        /**
         * Get the style tag for background-color for a player name (shadow for white text)
         * @param {Object} player 
         * @returns css tag or empty string
         */
         colorBg: function(player) {
            let color_bg = "";
            if (player.color_back) {
                color_bg = "background-color:#"+player.color_back+";";
            } else if (player.color == "FFF") {
                color_bg = WHITE_OUTLINE;
            }
            return color_bg;
        },

        /**
         * This method will remove all inline style added to element that affect positioning
         */
         stripPosition: function (token) {
            // console.log(token + " STRIPPING");
            // remove any added positioning style
            token = $(token);

            token.style.removeProperty("display");
            token.style.removeProperty("top");
            token.style.removeProperty("bottom");
            token.style.removeProperty("left");
            token.style.removeProperty("right");
            token.style.removeProperty("position");
            // dojo.style(token, "transform", null);
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
         * Check whether an element is highlighted.
         * @param {DOM} element 
         * @returns true if element is highlighted
         */
        isHighlighted: function(element) {
            return element.dataset.highlight == "true";
        },

        /**
         * Remove the highlight data tag from an element.
         * @param {DOM} element 
         */
        unhighlight: function(element) {
            delete element.dataset.highlight;
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