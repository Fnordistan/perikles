/**
 * locationtile.js: factory to for Influence Tiles
 * 
 */

// row,column
const LOCATION_TILES = {
    "amphipolis" : {xy: [1,1], city: "athens", "rounds": "TH", "vp": 6, "intrinsic": "dh"},
    "lesbos" : {xy: [1,2], city: "athens", "rounds": "HT", "vp": 4, "intrinsic": "aht"},
    "plataea" :  {xy: [1,3], city: "athens", "rounds": "H", "vp": 4, "intrinsic": "dh"},
    "naupactus" : {xy: [1,4], city: "athens", "rounds": "TH", "vp": 4, "intrinsic": null},
    "potidea" : {xy: [1,5], city: "athens", "rounds": "TH", "vp": 5, "intrinsic": "ah"},
    "acarnania" : {xy: [1,6], city: "athens", "rounds": "TH", "vp": 3, "intrinsic": "dh"},
    "attica" : {xy: [1,7], city: "athens", "rounds": "H", "vp": 4, "intrinsic": null},
    "melos" : {xy: [2,1], city: "sparta", "rounds": "HT", "vp": 3, "intrinsic": "dht"},
    "epidaurus" : {xy: [2,2], city: "sparta", "rounds": "TH", "vp": 4, "intrinsic": null},
    "pylos" : {xy: [2,3], city: "sparta", "rounds": "TH", "vp": 4, "intrinsic": null},
    "sicily" : {xy: [2,4], city: "sparta", "rounds": "TH", "vp": 7, "intrinsic": "dht"},
    "cephallenia" : {xy: [2,5], city: "sparta", "rounds": "HT", "vp": 4, "intrinsic": null},
    "cythera" : {xy: [2,6], city: "sparta", "rounds": "HT", "vp": 3, "intrinsic": null},
    "spartolus" : {xy: [2,7], city: "sparta", "rounds": "TH", "vp": 4, "intrinsic": "ah"},
    "megarabattle" : {xy: [3,1], city: "megara", "rounds": "TH", "vp": 5, "intrinsic": null},
    "mantinea" : {xy: [3,2], city: "argos", "rounds": "H", "vp": 5, "intrinsic": null},
    "delium" : {xy: [3,3], city: "thebes", "rounds": "TH", "vp": 5, "intrinsic": null},
    "aetolia" : {xy: [3,4], city: "thebes", "rounds": "TH", "vp": 3, "intrinsic": null},
    "corcyra" : {xy: [3,5], city: "corinth", "rounds": "HT", "vp": 3, "intrinsic": "aht"},
    "leucas" : {xy: [3,6], city: "corinth", "rounds": "HT", "vp": 4, "intrinsic": null},
    "solygeia" : {xy: [3,7], city: "corinth", "rounds": "HT", "vp": 4, "intrinsic": null},
}

const TILE_WIDTH = 124;
const TILE_HEIGHT = 195;
const TILE_SCALE = 0.55;

const WAR = -1;
const ALLIED = 1;
const NEUTRAL = 0;

define(["dojo/_base/declare"], function (declare) {
    return declare("perikles.locationtile", null, {
        
        /**
         * Initialize with the game's players.
         */
        constructor: function(location) {
            this.location = location;
            this.city = LOCATION_TILES[location]['city'];
            this.rounds = LOCATION_TILES[location]['rounds'];
            this.intrinsic = LOCATION_TILES[location]['intrinsic'];
            this.vp = LOCATION_TILES[location]['vp'];
            this.xy = LOCATION_TILES[location]['xy'];
            this.name = this.getBattleNameTr(location);
        },

        /**
         * Returns the location name.
         * @returns {string}
         */
        getLocation: function() {
            return this.location;
        },

        /**
         * Returns translateable name string.
         * @returns {string}
         */
        getNameTr: function() {
            return this.name;
        },

        /**
         * Get the city this location tile belongs to.
         * @returns {string}
         */
        getCity: function() {
            return this.city;
        },

        /**
         * Get the VP value of this tile.
         * @returns {int}
         */
        getVP: function() {
            return this.vp;
        },

        /**
         * Get the battle round code for this tile.
         * @returns {string
         */
        getRounds: function() {
            return this.rounds;
        },

        /**
         * Get code for intrinsic attackers or defenders. (May be null.)
         * @returns {string}
         */
        getMilitia: function() {
            return this.intrinsic;
        },

        /**
         * @returns {int} x coordinate
         */
        x: function() {
            return this.xy[0];
        },

        /**
         * @returns {int} y coordinate
         */
        y: function() {
            return this.xy[1];
        },

        /**
         * Create location tile html.
         * @param {int} m margin optional
         * @param {string} tag optional append to id
         * @returns {string} html div
         */
         createTile: function(m=0, tag=null) {
            const xoff = -1 * (this.y()-1) * TILE_WIDTH * TILE_SCALE;
            const yoff = -1 * (this.x()-1) * TILE_HEIGHT * TILE_SCALE;
            let id = this.location+'_tile';
            if (tag != null) {
                id += '_'+tag;
            }
            const html = '<div id="'+id+'" class="prk_location_tile" style="background-position: '+xoff+'px '+yoff+'px; margin: '+m+'px;"></div>'
            return html;
        },

        /**
         * Create location tile HTML for log messages.
         * @returns  {string} html div for logs
         */
        createIcon: function() {
            const xoff = -1 * (this.y()-1) * TILE_WIDTH * TILE_SCALE;
            const yoff = -1 * (this.x()-1) * TILE_HEIGHT * TILE_SCALE;
            const html = '<div class="prk_location_tile_log" style="background-position: '+xoff+'px '+yoff+'px;"></div>'
            return html;
        },

        /**
         * Create the HTML tooltip for Location cards.
         * @param {string} translated City name
         */
         createTooltip: function(cityName) {
            let desc = this.createBattleDescription();
            desc += '<br/>';
            desc += this.createBonusDescription();
            desc += '<br/>';
            let vpstr = _("${vp} Victory Points");
            vpstr = vpstr.replace('${vp}', this.vp);
            desc += vpstr;
            const x = -1 * (this.y()-1) * TILE_WIDTH * TILE_SCALE;
            const y = -1 * (this.x()-1) * TILE_HEIGHT * TILE_SCALE;

            let defendingcity = _("Defender: ${cityname}");
            defendingcity = defendingcity.replace('${cityname}', cityName);
            const tt = '<div style="display: flex; flex-direction: row;">\
                                <div class="prk_location_tile" style="background-position: '+x+'px '+y+'px; margin: 5px;"></div>\
                                <div style="flex: 1;">\
                                    <h1 style="font-family: Bodoni Moda;">'+this.getNameTr()+'</h1>\
                                    <h2>'+defendingcity+'</h2>\
                                    <span style="font-size: 22px;">'+desc+'</span>\
                                </div>\
                            </div>';
            return tt;
        },

        /**
         * Tooltip for tile after it's claimed.
         */
        createVictoryTileTooltip: function() {
            let vpstr = _("${vp} Victory Points");
            vpstr = vpstr.replace('${vp}', this.vp);
            const x = -1 * (this.y()-1) * TILE_WIDTH * TILE_SCALE;
            const y = -1 * (this.x()-1) * TILE_HEIGHT * TILE_SCALE;

            const tt = '<div style="display: flex; flex-direction: row;">\
                                <div class="prk_location_tile" style="background-position: '+x+'px '+y+'px; margin: 5px;"></div>\
                                <div style="flex: 1;">\
                                    <h1 style="font-family: Bodoni Moda;">'+this.getNameTr()+'</h1>\
                                    <span style="font-size: 22px;">'+vpstr+'</span>\
                                </div>\
                            </div>';
            return tt;
        },

        /**
         * Translated battle description string
         * @param {string} rounds TH/HT/H 
         */
         createBattleDescription: function() {
            let battlestr = _("Order of Battle: ${units}");
            const trireme = _("Triremes");
            const hoplite = _("Hoplites");
            const desc = {
                "H": hoplite,
                "HT": hoplite+'&#10142;'+trireme,
                "TH": trireme+'&#10142;'+hoplite,
            };
            battlestr = battlestr.replace('${units}', desc[this.rounds]);
            return battlestr;
        },

        /**
         * Translated string describing location tile native attackers/defenders.
         * @param {string} intrinsic 
         */
         createBonusDescription: function() {
            let desc = "";
            if (this.intrinsic != null) {
                const attacker = _("Attacker");
                const defender = _("Defender");
                const both = _("Hoplites and Triremes");
                const hoplite = _("Hoplites");
                let bonusstr = _("${combatant} adds 1 to ${unit} strength");
                let combatant = "";
                let units = "";
                switch (this.intrinsic) {
                    case "ah":
                        combatant = attacker;
                        units = hoplite;
                        break;
                    case "dh":
                        combatant = defender;
                        units = hoplite;
                        break;
                    case "aht":
                        combatant = attacker;
                        units = both;
                        break;
                    case "dht":
                        combatant = defender;
                        units = both;
                        break;
               }
               bonusstr = bonusstr.replace('${combatant}', combatant);
               bonusstr = bonusstr.replace('${unit}', units);
               desc = bonusstr;
            }
            return desc;
        },

        /**
         * Get all military counters assigned to the battle zones on one side of a Location Tile.
         * @param {string} side attacker or defender
         * @returns array of counters on the given side of the location tile
         */
        getUnits: function(side) {
            units = [];
            const parent = $(this.location+'_tile').parentNode.parentNode;
            const battlezones = parent.getElementsByClassName("prk_battle");
            [...battlezones].forEach(z => {
                if (z.dataset.side == side) {
                    counters = z.getElementsByClassName("prk_military");
                    units = [...units, ...counters];
                }
            });
            return units;
        },

        /**
         * Return translatable location name text.
         * @param {string} battle 
         * @returns translatable string
         */
         getBattleNameTr: function(battle) {
            const locationnames = {
                "amphipolis" : _("Amphipolis"),
                "lesbos" : _("Lesbos"),
                "plataea" : _("Plataea"),
                "naupactus" : _("Naupactus"),
                "potidea" : _("Potidea"),
                "acarnania" : _("Acarnania"),
                "attica" : _("Attica"),
                "melos" : _("Melos"),
                "epidaurus" : _("Epidaurus"),
                "pylos" : _("Pylos"),
                "sicily" : _("Sicily"),
                "cephallenia" : _("Cephallenia"),
                "cythera" : _("Cythera"),
                "spartolus" : _("Spartolus"),
                "megarabattle" : _("Megara"),
                "mantinea" : _("Mantinea"),
                "delium" : _("Delium"),
                "aetolia" : _("Aetolia"),
                "corcyra" : _("Corcyra"),
                "leucas" : _("Leucas"),
                "solygeia" : _("Solygeia"),
            };
            return locationnames[battle];
        },
    })
});