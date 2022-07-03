/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Perikles implementation : © <David Edelstein> <david.edelstein@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * perikles.js
 *
 * Perikles user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

const BOARD_SCALE = 2;
const INFLUENCE_SCALE = 0.5;

const CITIES = ['athens', 'sparta', 'argos', 'corinth', 'thebes', 'megara'];

const INFLUENCE_ROW = {'athens' : 0, 'sparta' : 1, 'argos' : 2, 'corinth' : 3, 'thebes' : 4, 'megara' : 5, 'any' : 6};
const INFLUENCE_COL = {'influence' : 0, 'candidate' : 1, 'assassin' : 2};

const INFLUENCE_PILE = "influence_slot_0";

const COMMIT_INFLUENCE_CUBES = "commit_influence_cubes";

const PLAYER_INF_MARGIN = "2px";

const SPECIAL_TILES = ['perikles', 'persianfleet', 'slaverevolt', 'brasidas', 'thessalanianallies', 'alkibiades', 'phormio', 'plague'];

const MILITARY_DISPLAY_STATES = ['spartanChoice', 'nextPlayerCommit', 'commitForces', 'deadPool', 'takeDead', 'resolveBattles'];

const CANDIDATES = {
    "\u{003B1}" : "a",
    "\u{003B2}" : "b"
}

const WHITE_OUTLINE = 'text-shadow: 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;';

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
    "megara" : {xy: [3,1], city: "megara", "rounds": "TH", "vp": 5, "intrinsic": null},
    "mantinea" : {xy: [3,2], city: "argos", "rounds": "H", "vp": 5, "intrinsic": null},
    "delium" : {xy: [3,3], city: "thebes", "rounds": "TH", "vp": 5, "intrinsic": null},
    "aetolia" : {xy: [3,4], city: "thebes", "rounds": "TH", "vp": 3, "intrinsic": null},
    "corcyra" : {xy: [3,5], city: "corinth", "rounds": "HT", "vp": 3, "intrinsic": "aht"},
    "leucas" : {xy: [3,6], city: "corinth", "rounds": "HT", "vp": 4, "intrinsic": null},
    "solygeia" : {xy: [3,7], city: "corinth", "rounds": "HT", "vp": 4, "intrinsic": null},
}

const DEAD_POOL = "deadpool";

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/zone",
    g_gamethemeurl + "modules/alkibiades.js",
    g_gamethemeurl + "modules/slaverevolt.js",
    g_gamethemeurl + "modules/stack.js",
    g_gamethemeurl + "modules/counter.js",
    g_gamethemeurl + "modules/decorator.js",
],
function (dojo, declare) {
    return declare("bgagame.perikles", [ebg.core.gamegui, perikles.alkibiades, perikles.slaverevolt, perikles.stack, perikles.counter, perikles.decorator], {
        constructor: function(){
            this.influence_h = 199;
            this.influence_w = 128;
            this.location_w = 124;
            this.location_h = 195;
            this.location_s = 0.55;

            this.stacks = new perikles.stack();
            this.slaverevolt = new perikles.slaverevolt();
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas ) {
            this.decorator = new perikles.decorator(gamedatas.players);

            this.setupSpecialTiles(gamedatas.players, gamedatas.specialtiles);
            this.setupInfluenceTiles(gamedatas.influencetiles, parseInt(gamedatas.decksize));
            this.setupInfluenceCubes(gamedatas.influencecubes);
            this.setupLocationTiles(gamedatas.locationtiles);
            this.setupCandidates(gamedatas.candidates);
            this.setupLeaders(gamedatas.leaders);
            this.setupStatues(gamedatas.statues);
            this.setupMilitary(gamedatas.military);
            this.setupDefeats(gamedatas.defeats);
            this.setupCities();


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
        },

        /**
         * Set up special tiles
         * @param {Array} players 
         * @param {Array} specialtiles 
         */
        setupSpecialTiles: function(players, specialtiles) {
            const special_scale = 0.2;

            for (const player_id in players) {
                const spec = parseInt(specialtiles[player_id]);

                // add flex row for cards
                const player_cards = this.format_block('jstpl_influence_cards', {id: player_id, scale: special_scale});
                const player_cards_div = dojo.place(player_cards, $('player_board_'+player_id));

                let used = false;
                if (spec == 0) {
                    var specialtile = this.format_block('jstpl_special_back', {id: player_id, scale: special_scale});
                } else {
                    const spec_i = SPECIAL_TILES[Math.abs(spec)-1];
                    specialtile = this.format_block('jstpl_special_tile', {special: spec_i, scale: special_scale, margin: PLAYER_INF_MARGIN});
                    used = (spec < 0 || player_id != this.player_id);
                }
                const tile = dojo.place(specialtile, player_cards_div);
                if (used) {
                    tile.classList.add("prk_special_tile_used");
                }
                if (spec == 0) {
                    let ttext = _("${player_name}'s Special tile");
                    const player_name = this.decorator.spanPlayerName(player_id);
                    ttext = ttext.replace('${player_name}', player_name);
                    this.addTooltip(tile.id, ttext, '');
                } else {
                    const thtml = this.createSpecialTileTooltip(SPECIAL_TILES[Math.abs(spec)-1]);
                    this.addTooltipHtml(tile.id, thtml, '');
                }
            }
        },

        /**
         * HTML for Special tile tooltip.
         * @param {string} tilenum 
         * @returns 
         */
        createSpecialTileTooltip: function(special) {
            const TITLES = {
                'perikles': _("PERIKLES"),
                'persianfleet': _("PERSIAN FLEET"),
                'slaverevolt': _("SLAVE REVOLT"),
                'brasidas': _("BRASIDAS"),
                'thessalanianallies': _("THESSALANIAN ALLIES"),
                'alkibiades': _("ALKIBIADES"),
                'phormio': _("PHORMIO"),
                'plague': _("PLAGUE")
            };
            const DESC = {
                'perikles': _("Place two Influence cubes in Athens. This tile can be played when it is your turn to select an Influence tile, either just before or just after taking the tile."),
                'persianfleet': _("This tile can be played just before a trireme battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                'slaverevolt': _("This tile can be played when it is your turn to commit forces to a location. Take one Spartan hoplite counter, either from the board or from the controlling player, and place it back in Sparta. That counter cannot be involved in combat this turn. You cannot examine the counter you remove. (It is selected randomly.) The counter will come back into play in the next turn."),
                'brasidas': _("This tile can be played just before a hoplite battle is about to be resolved. All Spartan hoplite counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                'thessalanianallies': _("This tile can be played just before a hoplite battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                'alkibiades': _("Player can take two Influence cubes of any color from any city/cities and move them to any city of their choice. These cubes may not be moved from a candidate space, nor may they be moved to one."),
                'phormio': _("This tile can be played just before a trireme battle is about to be resolved. All Athenian trireme counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                'plague': _("This tile can be played during the Influence Tile phase. Select one city. All players remove half (rounded down) of their Influence cubes from that city.")
            };

            const title = TITLES[special];
            const text = DESC[special];
            const tt = this.format_block('jstpl_special_tt', {header: title, special: special, text: text, scale: 0.5});
            return tt;
        },

        /**
         * Create the HTML tooltip for Location cards.
         */
        createLocationTileTooltip: function(location) {
            const battlename = this.getBattleNameTr(location);

            let desc = this.createBattleDescription(LOCATION_TILES[location].rounds);
            desc += '<br/>';
            desc += this.createBonusDescription(LOCATION_TILES[location].intrinsic);
            desc += '<br/>';
            let vpstr = _("${vp} Victory Points");
            vpstr = vpstr.replace('${vp}', LOCATION_TILES[location].vp);
            desc += vpstr;
            const city = LOCATION_TILES[location].city;
            const x = -1 * (LOCATION_TILES[location].xy[1]-1) * this.location_w * this.location_s;
            const y = -1 * (LOCATION_TILES[location].xy[0]-1) * this.location_h * this.location_s;

            let defendingcity = _("Defender: ${cityname}");
            defendingcity = defendingcity.replace('${cityname}', this.getCityNameTr(city));
            const tt = this.format_block('jstpl_location_tt', {defender: defendingcity, battle: battlename, text: desc, x: x, y: y});
            return tt;
        },

        /**
         * Translated battle description string
         * @param {string} rounds TH/HT/H 
         */
        createBattleDescription: function(rounds) {
            let battlestr = _("Order of Battle: ${units}");
            const trireme = _("Triremes");
            const hoplite = _("Hoplites");
            const desc = {
                "H": hoplite,
                "HT": hoplite+'&#10142;'+trireme,
                "TH": trireme+'&#10142;'+hoplite,
            };
            battlestr = battlestr.replace('${units}', desc[rounds]);
            return battlestr;
        },

        /**
         * Translated string describing location tile native attackers/defenders.
         * @param {string} intrinsic 
         */
        createBonusDescription: function(intrinsic) {
            let desc = "";
            if (intrinsic != null) {
                const attacker = _("Attacker");
                const defender = _("Defender");
                const both = _("Hoplites and Triremes");
                const hoplite = _("Hoplites");
                let bonusstr = _("${combatant} adds 1 to ${unit} strength");
                let combatant = "";
                let units = "";
                switch (intrinsic) {
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
         * Put influence tiles on board, create deck
         * @param {Array} influence
         * @param {int} decksize
         */
        setupInfluenceTiles: function(influence, decksize) {
            // tiles in slots
            for (const tile of influence) {
                const loc = tile['location'];
                if (loc == "board") {
                    this.placeInfluenceTileBoard(tile);
                } else {
                    this.placeInfluenceTilePlayerBoard(tile);
                }
            }
            // deck
            this.createInfluencePile(decksize);
        },

        /**
         * 
         * @param {Object} tile 
         */
        placeInfluenceTileBoard: function(tile) {
            const city = tile['city'];
            const s = tile['slot'];

            const card_div = this.createInfluenceCard(tile);
            const card = dojo.place(card_div,  $("influence_slot_"+s));

            this.decorateInfluenceCard(card, city, tile['type']);
        },

        /**
         * 
         * @param {Object} tile 
         */
        placeInfluenceTilePlayerBoard: function(tile) {
            const loc = tile['location'];
            const player_cards = loc+'_player_cards';
            const card_div = this.createInfluenceCard(tile);
            dojo.place(card_div, $(player_cards));
        },

        /**
         * 
         * @param {Object} tile 
         * @returns Div string
         */
        createInfluenceCard: function(tile) {
            const id = tile['id'];
            const city = tile['city'];
            const xoff = -1 * INFLUENCE_COL[tile['type']] * INFLUENCE_SCALE * this.influence_w;
            const yoff = -1 * INFLUENCE_ROW[city] * INFLUENCE_SCALE * this.influence_h;
            const card_div = this.format_block('jstpl_influence_tile', {id: id, city: city, x: xoff, y: yoff, margin: PLAYER_INF_MARGIN});
            return card_div;
        },

        /**
         * Deck pile of decksize cardbacks
         * @param {int} decksize 
         */
        createInfluencePile: function(decksize) {
            for (let c = 1; c <= decksize; c++) {
                const cardback = this.format_block('jstpl_influence_back', {id: c, x: -1 * INFLUENCE_SCALE * this.influence_w, y: -6 * INFLUENCE_SCALE * this.influence_h, m: c-1});
                dojo.place(cardback, $(INFLUENCE_PILE));
            }
            var pile_tt = _("Influence Deck: ${num} cards remaining");
            pile_tt = pile_tt.replace('${num}', decksize);
            this.addTooltip(INFLUENCE_PILE, pile_tt, '');
        },

        /**
         * Return translatable city name text
         * @param {string} city 
         * @returns translatable string
         */
        getCityNameTr: function(city) {
            const citynames = {
                "argos" : _("Argos"),
                "athens": _("Athens"),
                "corinth": _("Corinth"),
                "megara": _("Megara"),
                "sparta": _("Sparta"),
                "thebes": _("Thebes"),
                "any": _("Any City"),
                "persia": _("Persia"),
            };
            return citynames[city];
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
                "megara" : _("Megara"),
                "mantinea" : _("Mantinea"),
                "delium" : _("Delium"),
                "aetolia" : _("Aetolia"),
                "corcyra" : _("Corcyra"),
                "leucas" : _("Leucas"),
                "solygeia" : _("Solygeia"),
            };
            return locationnames[battle];
        },

        /**
         * Get the translation string for a special tile
         * @param {string} special 
         */
        getSpecialTileTr: function(special) {
            const special_names = {
                'perikles': _("Perikles"),
                'persianfleet': _("Persian Fleet"),
                'slaverevolt': _("Slave Revolt"),
                'brasidas': _("Brasidas"),
                'thessalanianallies': _("Thessalanian Allies"),
                'alkibiades': _("Alkibiades"),
                'phormio': _("Phormio"),
                'plague': _("Plague")
            };
            return special_names[special];
        },

        /**
         * For Influence cards on display, add Event listeners.
         * @param {Object} card
         * @param {string} city
         * @param {string} type
         */
         decorateInfluenceCard: function(card, city, type) {
            const helplbl = {
                "influence": "",
                "candidate": _("Candidate"),
                "assassin": _("Assassin"),
                "any": "",
            };
            const helptext = {
                "influence": _("Add 2 Influence cubes to ${city}"),
                "candidate": _("Add 1 Influence cube to ${city}, and propose a candidate in any city"),
                "assassin": _("Add 1 Influence cube to ${city}, and remove 1 cube from any city"),
                "any": _("Add 1 Influence cube to any city"),
            };

            const cityname = this.getCityNameTr(city);
            let ttext = "";
            if (city == "any") {
                ttext = helptext["any"];
            } else {
                ttext = helptext[type];
            }

            ttext = ttext.replace('${city}', cityname);
            const xoff = -1 * INFLUENCE_COL[type] * INFLUENCE_SCALE * this.influence_w;
            const yoff = -1 * INFLUENCE_ROW[city] * INFLUENCE_SCALE * this.influence_h;
            const tooltip = this.format_block('jstpl_influence_tt', {city: cityname, label: helplbl[type], text: ttext, x: xoff, y: yoff});
            this.addTooltipHtml(card.id, tooltip, '');

            card.addEventListener('click', () => {
                this.onInfluenceCardSelected(card.id);
            });
            card.addEventListener('mouseenter', () => {
                this.onInfluenceCardHover(card.id, true);
            });
            card.addEventListener('mouseleave', () => {
                this.onInfluenceCardHover(card.id, false);
            });
        },

        /**
         * Place influence cubes on cities.
         * @param {Object} influencecubes 
         */
        setupInfluenceCubes: function(influencecubes) {
            for (const player_id of Object.keys(influencecubes)) {
                for (const [city, cubes] of Object.entries(influencecubes[player_id])) {
                    const num = parseInt(cubes);
                    for (let n = 0; n < num; n++) {
                        const cube = this.createInfluenceCube(player_id, city, n);
                        const column = $(city+"_cubes_"+player_id);
                        const cube_div = dojo.place(cube, column);
                        cube_div.addEventListener('click', (event) => this.onSelectCube(event));
                    }
                    this.decorateInfluenceCubes(city, player_id);
                }
            }
        },

        /**
         * Add event listeners to the cubes areas for each player.
         * @param {string} city
         * @param {string} player_id
         */
        decorateInfluenceCubes: function(city, player_id) {
            const id = city+"_cubes_"+player_id;
            const cubes_div = $(id);

            cubes_div.addEventListener('mouseenter', (event) => {
                this.onInfluenceCubesHover(event, city, true);
            });
            cubes_div.addEventListener('mouseleave', (event) => {
                this.onInfluenceCubesHover(event, city, false);
            });
            cubes_div.addEventListener('click', (event) => {
                this.onInfluenceCubesClick(event, city, player_id);
            });
        },

        /**
         * Put all the Location tiles in their slots.
         * @param {Array} locationtiles 
         */
        setupLocationTiles: function(locationtiles) {
            for (const loc of locationtiles) {
                const slot = loc['slot'];
                const battle = loc['battle'];
                const location = loc['loc'];
                const loc_html = this.createLocationTile(battle, 0);
                if (location == "board") {
                    const tile = dojo.place(loc_html, $("location_"+slot));
                    const lochtml = this.createLocationTileTooltip(battle);
                    this.addTooltipHtml(tile.id, lochtml, '');
                } else if (location == "unclaimed") {
                    const tile = dojo.place(loc_html, $("unclaimed_tiles"));
                    tile.style.margin = null;
                } else {
                    // player claimed
                }
            }
        },

        /**
         * Create location tile
         * @param {int} location 
         * @param {int} m margin
         * @returns html div
         */
        createLocationTile: function(location, m) {
            const x = -1 * (LOCATION_TILES[location].xy[1]-1) * this.location_w * this.location_s;
            const y = -1 * (LOCATION_TILES[location].xy[0]-1) * this.location_h * this.location_s;
            const loc_html = this.format_block('jstpl_location_tile', {id: location, x: x, y: y, m: m});
            return loc_html;
        },

        /**
         * Put cubes in candidate spaces.
         * @param {Object} candidates 
         */
        setupCandidates: function(candidates) {
            for (const [cand, player_id] of Object.entries(candidates)) {
                const cid = cand.split('_');
                const city = cid[0];
                const cube = this.createInfluenceCube(player_id, city, cid[1]);
                const candidate = dojo.place(cube, $(cand));
                candidate.addEventListener('click', (event) => this.onSelectCube(event));
            }
        },

        /**
         * Place Leader tokens on cities.
         * @param {Object} leaders 
         */
        setupLeaders: function(leaders) {
            for (const [city, player_id] of Object.entries(leaders)) {
                this.createLeaderCounter(player_id, city, "leader", 1);
            }
        },

        /**
         * Place all statues in city statue areas.
         * @param {Object} statues 
         */
        setupStatues: function(statues) {
            for (const city of CITIES) {
                const citystatues = statues[city];
                if (citystatues) {
                    let s = 0;
                    for (const [player_id, num] of Object.entries(citystatues)) {
                        for (let i = 1; i <= parseInt(num); i++) {
                            const statue_div = this.createLeaderCounter(player_id, city, "statue", s+1);
                            const statue = dojo.place(statue_div, $(city+"_statues"));
                            statue.style.bottom = (s*22)+"px";
                            statue.style.left = (s*6)+"px";
                            s++;
                        }
                    }
                }
            }
        },

        /**
         * For creating Leader and Statue counters.
         * @param {int} player_id 
         * @param {string} city 
         * @param {string} type 
         * @param {int} n 
         * @returns statue or leader div
         */
        createLeaderCounter: function(player_id, city, type, n) {
            const leaderhtml = this.format_block('jstpl_leader', {city: city, type: type, num: n, color: this.decorator.playerColor(player_id)});
            const leader = dojo.place(leaderhtml, $(city+"_leader"));
            let tt = _("${player_name} is Leader of ${city_name}");
            tt = tt.replace('${player_name}', this.decorator.spanPlayerName(player_id));
            tt = tt.replace('${city_name}', this.getCityNameTr(city));
            this.addTooltip(leader.id, tt, '');
        },

        /**
         * Create a cube div in player's color.
         * @param {int} player_id
         * @param {string} city
         * @param {string} tag
         * @returns html for colored influence cube
         */
        createInfluenceCube: function(player_id, city, tag) {
            const player = this.gamedatas.players[player_id];
            const color = player.color;
            const id = player_id+"_"+city+"_"+tag;
            const cube = this.format_block('jstpl_cube', {id: id, color: color});
            return cube;
        },

        /**
         * Place all military counters
         * @param {Object} military 
         */
        setupMilitary: function(military) {
            // first add Persia
            this.createStack("persia");
            // stack for each city
            for (const city of CITIES) {
                this.createStack(city);
            }

            for(const mil of military) {
                const city = mil['city'];
                const unit = mil['type'];
                const counter = this.militaryToCounter(mil);
                if (counter.getLocation() == counter.getCity() && counter.getPosition() == 0) {
                    // in a city stack
                    counter.addToStack();
                } else if (counter.getLocation() == DEAD_POOL) {
                    // in the dead pool

                } else if (Object.keys(LOCATION_TILES).includes(counter.getLocation())) {
                    // sent to a battle
                    this.placeCounterAtBattle(counter);
                } else {
                    // it's in a player pool
                    const player_id = counter.getLocation();
                    if (player_id == this.player_id) {
                        this.createMilitaryArea(player_id, city);
                        const counter_div = counter.toDiv(1, 0);
                        const mil_zone = city+"_"+unit+"_"+player_id;
                        const counterObj = dojo.place(counter_div, $(mil_zone));
                        Object.assign(counterObj.style, {position: "relative"});
                        counterObj.setAttribute("title", this.counterText(counter));
                    }
                }
            }
        },

        /**
         * Factory method: create a counter from the PHP object in datas.
         * @param {Object} military 
         * @returns perikles.counter
         */
        militaryToCounter: function(military) {
            const city = military['city'];
            const unit = military['type'];
            const strength = military['strength'];
            const id = military['id'];
            const location = military['location'];
            const position = military['battlepos'];
            const counter = new perikles.counter(city, unit, strength, id, location, position);
            return counter;
        },

        /**
         * Create a military stack, add a tooltip.
         * @param {string} city 
         */
        createStack: function(city) {
            const stack = city+"_military";
            this.stacks.decorateMilitaryStack(stack);
            const city_name = this.getCityNameTr(city);
            let tt = _("${city} military: click to inspect stack");
            tt = tt.replace('${city}', city_name);
            this.addTooltip(stack, tt, '');
        },

        /**
         * Place a military counter on the battle stacks at a location tile.
         * @param {Object} counter 
         */
        placeCounterAtBattle: function(counter) {
            const slotid = $(counter.getLocation()+"_tile").parentNode.id;
            const slot = slotid[slotid.length-1];
            const place = "battle_"+slot+"_"+counter.getType()+"_"+counter.getBattlePosition();
            const stackct = $(place).childElementCount;
            // zero ids for face-down units
            if (counter.getStrength() == 0) {
                counter.setId("counter_"+stackct);
            } 
            const battlecounter = counter.toBattleDiv(stackct);
            dojo.place(battlecounter, $(place));
        },

        /**
         * Place Defeat counters on cities.
         * @param {Object} defeats 
         */
        setupDefeats: function(defeats) {
            for (const [city, num] of Object.entries(defeats)) {
                for (let d = 1; d <= num; d++) {
                    const def_ctr = this.format_block('jstpl_defeat', {city: city, num: d} );
                    const def_div = $(city+'_defeat_slot_'+d);
                    dojo.place(def_ctr, def_div);
                }
            }
        },

        /**
         * Add event listeners to city divs.
         */
        setupCities: function() {
            for (const city of CITIES) {
                $(city).addEventListener('mouseenter', () => {
                    this.onCityTouch(city, true);
                });
                $(city).addEventListener('mouseleave', () => {
                    this.onCityTouch(city, false);
                });
                $(city).addEventListener('click', () => {
                    this.onCityClick(city);
                });
            }
        },

        ///////////////////////////////////////////////////
        //// Display methods

        /* @Override */
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true;
                    if (args.player_name && args.player_id) {
                        args.player_name = this.decorator.spanPlayerName(args.player_id);
                    }
                    if (args.actplayer) {
                        args.actplayer = args.actplayer.replace('color:#FFF;', 'color:#FFF;'+WHITE_OUTLINE);
                    }
                    if (args.candidate_name) {
                        args.candidate_name  = this.decorator.spanPlayerName(args.candidate_id);
                    }
                    if (args.city_name) {
                        args.city_name = this.spanCityName(args.city);
                    }
                    if (args.city_name2) {
                        args.city_name2 = this.spanCityName(args.city2);
                    }
                    if (args.special_tile) {
                        args.special_tile = '<span class="prk_special_log">'+args.special_tile+'</span>';
                    }
                    if (args.attd1) {
                        args.attd1 = this.diceIcon(args.attd1);
                        args.attd2 = this.diceIcon(args.attd2);
                        args.defd1 = this.diceIcon(args.defd1, true);
                        args.defd2 = this.diceIcon(args.defd2, true);
                        args.atttotal = '<span class="prk_dicetotal">['+args.atttotal+']</span>';
                        args.deftotal = '<span class="prk_dicetotal">['+args.deftotal+']</span>';
                        args.atttotal = '<span>'+args.atttotal+'</span>';
                    }
                    if (!this.isSpectator) {
                        log = log.replace("You", this.decorator.spanYou(this.player_id));

                        if (args.committed) {
                            const commit_log = this.createCommittedUnits(args.committed);
                            log = log.replace('committed_forces', commit_log);
                        }
                        if (args.plague) {
                            const plague_btns = this.createPlagueButtons();
                            log += plague_btns;
                        }
                        if (args.alkibiades) {
                            const alkibiades_btns = this.createAlkibiadesButtons();
                            log += alkibiades_btns;
                        }
                        if (args.slaverevolt) {
                            const slaverevolt_btns = this.createSlaveRevoltButtons();
                            log += slaverevolt_btns;
                        }
                    }
                }
            } catch (e) {
                console.error(log, args, "Exception thrown", e.stack);
            }
            return this.inherited(arguments);
        },

        /**
         * Show all the units that have been assigned to send to battles and display in commit dialog.
         * @param {Object} committed Object from args
         */
        createCommittedUnits: function(committed) {
            let commit_log = "";
            const attack_str = _("Send ${unit} to attack ${location}");
            const defend_str = _("Send ${unit} to defend ${location}");
            let counters = 0;
            // if spent cube from city for extra units
            const commit_city = committed.cube;
            let extra_forces = "";
            for (const[id, selected] of Object.entries(committed)) {
                if (id != "cube") {
                    let commit_str = (selected.side == "attack" ? attack_str : defend_str);
                    const counter = new perikles.counter(selected.city, selected.unit, selected.strength, id+"_dlg");
                    let mil_html = counter.toRelativeDiv();
                    mil_html = this.decorator.prependStyle(mil_html, 'display: inline-block');
                    commit_str = commit_str.replace('${unit}', mil_html);
                    let loc_html = this.createLocationTile(selected.location, 0);
                    loc_html = this.decorator.prependStyle(loc_html, 'display: inline-block');
                    commit_str = commit_str.replace('${location}', loc_html);

                    if (selected.cube) {
                        extra_forces += commit_str+'<br/>';
                    } else {
                        commit_log += commit_str+'<br/>';
                    }
                    counters++;
                }
            }
            // option to spend Influence cube
            if (counters >= 2) {
                commit_log += '<hr/>';
                if (commit_city) {
                    let city_commit = _("Additional unit(s) committed from ${city}");
                    city_commit = city_commit.replace('${city}', this.spanCityName(commit_city));
                    commit_log += city_commit + '<br/>';
                    commit_log += extra_forces;
                } else {
                    let spend_cubes_div = this.createSpendInfluenceDiv();
                    if (spend_cubes_div != null) {
                        let cubehtml = this.createInfluenceCube(this.player_id, 'commit', '');
                        cubehtml = this.decorator.prependStyle(cubehtml, "display: inline-block; margin-left: 5px;");
                        spend_cubes_div = _("You may spend an Influence cube to send 1 or 2 units from that city")+cubehtml+'<br/>'+spend_cubes_div;
                        commit_log += spend_cubes_div;
                    }
                }
            }
            commit_log += '<br/>';
            return commit_log;
        },

        /**
         * Create die icon.
         * @param {string} val 
         * @param {bool} def is defensive dice
         * @returns html icon
         */
        diceIcon: function(val, def=false) {
            const roll = toint(val);
            const xoff = -33 * (roll-1);
            let die_icon = this.format_block('jstpl_die', {x: xoff});
            if (def) {
                const def_color = "filter: sepia(100%)";
                die_icon = this.decorator.prependStyle(die_icon, def_color);
            }
            return die_icon;
        },

        /**
         * Take a city name and put it in colored text and translate the name.
         * @param {string} city 
         * @returns decorated HTML div
         */
        spanCityName: function(city) {
            let cityclass = "prk_city_name";
            if (city != "any") {
                cityclass += " prk_shadow"
            }
            const city_name = '<div class="'+cityclass+'" style="color:var(--color_'+city+');">'+this.getCityNameTr(city)+'</div>';
            return city_name;
        },

        /**
         * Puts top banner for active player.
         * @param {string} text
         * @param {Array} moreargs
         */
         setDescriptionOnMyTurn: function(text, moreargs) {
            const oldtext = this.isCurrentPlayerActive() ? this.gamedatas.gamestate.descriptionmyturn : this.gamedatas.gamestate.description;
            this.gamedatas.gamestate.olddescriptionmyturn = oldtext;
            this.gamedatas.gamestate.descriptionmyturn = text;
            this.gamedatas.gamestate.oldargs = this.gamedatas.gamestate.args;
            let tpl = Object.assign({}, this.gamedatas.gamestate.args);
            if (!tpl) {
                tpl = {};
            }
            if (typeof moreargs != 'undefined') {
                for ( const key in moreargs) {
                    if (moreargs.hasOwnProperty(key)) {
                        tpl[key]=moreargs[key];
                    }
                }
            }
 
            let title = "";
            if (this.isCurrentPlayerActive() && text !== null) {
                tpl.you = this.decorator.spanYou(this.player_id);
            }
            if (text !== null) {
                title = this.format_string_recursive(text, tpl);
            }
            if (title == "") {
                this.decorator.setMainTitle("&nbsp;");
            } else {
                this.decorator.setMainTitle(title);
            }
        },

        /**
         * Restore title text.
         */
        restoreDescriptionOnMyTurn: function() {
            const text = this.gamedatas.gamestate.olddescriptionmyturn;
            if (text) {
                const acting = this.decorator.spanPlayerName(this.getActivePlayerId());
                this.setDescriptionOnMyTurn(text, {actplayer: acting});
            }
        },

        /**
         * Create a military area for player, if it does not already exist
         * @param {string} player_id 
         * @param {strin} city 
         * @returns id of military div
         */
        createMilitaryArea: function(player_id, city) {
            const city_mil = city+'_military_'+player_id;
            if (!document.getElementById(city_mil)) {
                const mil_div = this.format_block('jstpl_military_area', {city: city, id: player_id, cityname: this.getCityNameTr(city)});
                dojo.place(mil_div, $('mymilitary'));
            }
            return city_mil;
        },

        /**
         * Move a cube from one location to another.
         * @param {string} cube 
         * @param {DOMElement} from_div 
         * @param {DOMElement} to_div
         * @param {int} delay
         */
         moveCube: function(cube, from_div, to_div, delay) {
            const mobile = dojo.place(cube, from_div);
            mobile.addEventListener('click', (event) => this.onSelectCube(event));
            this.slideToObjectRelative(mobile, to_div, 1000, delay, null, "last")
        },

        /**
         * Move a military token from the city to the player's board
         * @param {Object} military
         */
        counterToPlayerBoard: function(military) {
            const counter = this.militaryToCounter(military);
            const player_id = military['location'];
            const counterObj = $(counter.getCounterId());
            if (player_id == this.player_id) {
                this.createMilitaryArea(player_id, counter.getCity());
                const mil_zone = counter.getCity()+"_"+counter.getType()+"_"+player_id;
                this.slideToObjectRelative(counterObj, $(mil_zone), 500, 500, null, "last");
            } else {
                this.slideToObjectAndDestroy(counterObj, $('player_board_'+player_id), 500, 500);
            }
            counterObj.setAttribute("title", this.counterText(counter));
        },

        /**
         * Move an object to a battle tile
         * @param {*} military 
         */
        moveToBattle: function(player_id, counter, slot) {
            // if it's my counter, remove it from my board
            if (player_id == this.player_id) {
                $(counter.getCity()+'_'+counter.getType()+'_'+counter.getStrength()+'_'+counter.getId()).remove();
            }
            // move from city to battle
            const battlepos = "battle_"+slot+"_"+counter.getType()+"_"+counter.getBattlePosition();
            const stackct = $(battlepos).childElementCount;
            let newId = counter.getId();
            if (newId == 0) {
                newId = stackct;
            }
            counter.setId("counter_"+newId);
            const counter_html = counter.toBattleDiv(stackct);
            const milzone = $(counter.getCity()+"_military");
            const counterObj = dojo.place(counter_html, milzone);
            this.slide(counterObj, battlepos, {from: milzone});
        },

        /**
         * Tisaac's slide method.
         * @param {DOMElement} mobile 
         * @param {string} targetId 
         * @param {Object} options 
         * @returns a Promise
         */
         slide: function(mobile, targetId, options = {}) {
            let config = Object.assign(
              {
                duration: 800,
                delay: 0,
                destroy: false,
                attach: true,
                changeParent: true, // Change parent during sliding to avoid zIndex issue
                pos: null,
                className: 'moving',
                from: null,
                clearPos: true,
              },
              options,
            );
            const newParent = config.attach ? targetId : $(mobile).parentNode;
            mobile.style['z-index'] = 5000;
            mobile.classList.add(config.className);
            // if (config.changeParent) {
            //     this.changeParent(mobile, 'game_play_area');
            // }
            if (config.from != null) {
                this.placeOnObject(mobile, config.from);
            }
            return new Promise((resolve, reject) => {
              const animation =
                config.pos == null
                  ? this.slideToObject(mobile, targetId, config.duration, config.delay)
                  : this.slideToObjectPos(mobile, targetId, config.pos.x, config.pos.y, config.duration, config.delay);
      
              dojo.connect(animation, 'onEnd', () => {
                // mobile.style['z-index'] = null;
                mobile.classList.remove(config.className);
                if (config.changeParent) {
                    this.changeParent(mobile, newParent);
                }
                if (config.destroy) {
                    mobile.parentNode.removeChild(mobile);
                }
                if (config.clearPos && !config.destroy) {
                    Object.assign(mobile.style, { top: null, left: null, position: null, 'z-index': null });
                }
                resolve();
              });
              animation.play();
            });
          },

          /**
           * 
           * @param {DOMObject} mobile 
           * @param {string} parent_id 
           */
          changeParent: function(mobile, parent_id) {
            $(parent_id).appendChild(mobile);
          },

        /*
         * This method is similar to slideToObject but works on object which do not use inline style positioning. It also attaches object to
         * new parent immediately, so parent is correct during animation
         */
        slideToObjectRelative: function (token, finalPlace, duration, delay, onEnd, relation) {
            token = $(token);
            this.delayedExec(() => {
                token.style.transition = "none";
                token.classList.add('moving_token');
                var box = this.attachToNewParentNoDestroy(token, finalPlace, relation, 'static');
                token.offsetHeight; // re-flow
                token.style.transition = "all " + duration + "ms ease-in-out";
                token.style.left = box.l + "px";
                token.style.top = box.t + "px";
            }, () => {
                token.style.removeProperty("transition");
                this.decorator.stripPosition(token);
                token.classList.remove('moving_token');
                // Perikles additions
                if (token.classList.contains("prk_military")) {
                    token.style.position = "relative";
                    token.style.margin = "1px";
                }
                if (onEnd) onEnd(token);
            }, duration, delay);
        },

        /**
         * This method will attach mobile to a new_parent without destroying, unlike original attachToNewParent which destroys mobile and
         * all its connectors (onClick, etc)
         */
         attachToNewParentNoDestroy: function (mobile_in, new_parent_in, relation, place_position) {
            //console.log("attaching ",mobile,new_parent,relation);
            const mobile = $(mobile_in);
            const new_parent = $(new_parent_in);

            var src = dojo.position(mobile);
            if (place_position) {
                mobile.style.position = place_position;
            }
            dojo.place(mobile, new_parent, relation);
            mobile.offsetTop;//force re-flow
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;

            mobile.style.position = "absolute";
            mobile.style.left = left + "px";
            mobile.style.top = top + "px";
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            mobile.offsetTop;//force re-flow
            return box;
        },

        /**
         * 
         * @param {*} onStart 
         * @param {*} onEnd 
         * @param {*} duration 
         * @param {*} delay 
         */
        delayedExec: function (onStart, onEnd, duration, delay) {
            if (typeof duration == "undefined") {
                duration = this.defaultAnimationDuration;
            }
            if (typeof delay == "undefined") {
                delay = 0;
            }
            if (this.instantaneousMode) {
                delay = Math.min(1, delay);
                duration = Math.min(1, duration);
            }
            if (delay) {
                setTimeout(function () {
                    onStart();
                    if (onEnd) {
                        setTimeout(onEnd, duration);
                    }
                }, delay);
            } else {
                onStart();
                if (onEnd) {
                    setTimeout(onEnd, duration);
                }
            }
        },
        
        ///////////////////////////////////////////////////
        //// Event listeners

        /**
         * Mouse entering City zone.
         * @param {string} city
         * @param {bool} enter true if touching, otherwise leaving
         */
        onCityTouch: function(city, enter) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    const mycubes = $(city+"_cubes_"+this.player_id);
                    if (enter) {
                        mycubes.classList.add("prk_cubes_active");
                    } else {
                        mycubes.classList.remove("prk_cubes_active");
                    }
                }
            }
        },

        /**
         * Mouse clicking City zone.
         * @param {string} city
         */
        onCityClick: function(city) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    this.placeInfluenceCube(city);
                    const mycubes = $(city+"_cubes_"+this.player_id);
                    mycubes.classList.remove("prk_cubes_active");
                }
            }
        },

        /**
         * Mouse entering or leaving player's Influence zone in city.
         * @param {Object} event 
         * @param {string} city
         * @param {bool} enter
         */
         onInfluenceCubesHover: function(event, city, enter) {
            if (this.isCurrentPlayerActive() && this.checkAction("proposeCandidate", true)) {
                const cube_div = event.target;
                // player must have a cube in the city
                if (enter && this.hasCubeInCity(city, true)) {
                    if (cube_div.hasChildNodes() && $(city).classList.contains("prk_city_active")) {
                        cube_div.classList.add("prk_cubes_active");
                    }
                } else {
                    cube_div.classList.remove("prk_cubes_active");
                }
            }
        },

        /**
         * Mouse clicking Influence zone in city.
         * @param {Object} event 
         * @param {string} city
         * @param {string} player_id
         */
         onInfluenceCubesClick: function(event, city, player_id) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("proposeCandidate", true)) {
                    const tgt = event.target;
                    // it's either the cube area or one of the cubes
                    if ($(city).classList.contains("prk_city_active") && this.hasCubeInCity(city, true)) {
                        if (tgt.classList.contains("prk_cube") || (tgt.classList.contains("prk_city_cubes") && tgt.hasChildNodes())) {
                            this.proposeCandidate(city, player_id);
                        }
    
                    }
                }
            }
        },

        /**
         * When Influence card is clicked.
         * @param {string} id 
         */
         onInfluenceCardSelected: function(id) {
            if (this.checkAction("takeInfluence", true)) {
                this.takeInfluenceTile(id);
            }
       },

       /**
        * When mouse hovers or leaves Influence tile.
        * @param {string} id 
        * @param {bool} hover true if entering, false if leaving
        */
        onInfluenceCardHover: function(id, hover) {
            if (this.checkAction("takeInfluence", true)) {
                const card = $(id);
                if (hover) {
                    card.classList.add("prk_influence_tile_active");
                } else {
                    card.classList.remove("prk_influence_tile_active");
                }
            }
        },

        /**
         * After clicking a cube during Assasassinate phase.
         */
        onSelectCube: function(event) {
            if (this.checkAction("chooseRemoveCube", true)) {
                const cube_id = event.target.id;
                const segs = cube_id.split("_");
                const player_id = segs[0];
                const city = segs[1];
                const c = segs[2];
                this.removeCube(player_id, city, c);
            }
        },

        /**
         * Send a unit to a battle location.
         * @param {*} id 
         * @param {*} city 
         * @param {*} unit 
         * @param {*} strength 
         * @param {*} side 
         * @param {*} battle 
         */
         onSendUnit: function(id, city, unit, strength, side, battle) {
            // is this an extra unit sent with a cube?
            const commit_city = this.gamedatas.gamestate.args.committed['cube'];
            const is_extra = (commit_city != null) && (commit_city == city);
            this.gamedatas.gamestate.args.committed[id] = {city: city, side: side, location: battle, strength: strength, unit: unit, cube: is_extra};
            this.setDescriptionOnMyTurn(_("You must commit forces")+'<br/>committed_forces');
            // add event listeners
            const city_btns = document.getElementsByClassName("prk_city_btn");
            [...city_btns].forEach(btn => {
                btn.addEventListener('click', this.onCommitExtraForces.bind(this));
            });
            // hide unit on military board
            $(city+'_'+unit+'_'+strength+'_'+id).style.display = "none";

            // don't forget the cube is one of the keys
            const len = Object.keys(this.gamedatas.gamestate.args.committed).length;
            // don't unselect anything if < 2
            if (len > 1) {
                let selectable_city = null;
                if (len > 2) {
                    if (commit_city) {
                        if (len == 4) {
                            selectable_city = commit_city;
                        } else if (len > 5) {
                            // sanity check 2
                            throw new Error("More than 4 units selected!");
                        }
                    } else {
                        // sanity check
                        throw new Error("More than 2 units selected without a cube!");
                    }
                }
                const mymil = $(mymilitary).getElementsByClassName("prk_military");
                [...mymil].forEach(m => this.makeSelectable(m, false));
                if (selectable_city) {
                    const selunits = $(selectable_city+"_mil_ctnr_"+this.player_id).getElementsByClassName("prk_military");
                    [...selunits].forEach(s => this.makeSelectable(s));
                }
            }
        },

        /**
         * Player clicks "Commit Forces" button.
         */
        onCommitForces: function() {
            const sz = Object.keys(this.gamedatas.gamestate.args.committed).length;
            if (sz == 0) {
                this.confirmationDialog( _("You have not selected any forces"), dojo.hitch( this, function() {
                    this.commitForces() 
                }));
            } else {
                this.commitForces();
            }
        },

        /**
         * Player clicked a city button to spend a cube on sending extra counters.
         */
        onCommitExtraForces: function(evt) {
            const target = evt.currentTarget;
            const city = target.id.split('_')[0];
            this.gamedatas.gamestate.args.committed['cube'] = city;
            // hide other buttons
            $(COMMIT_INFLUENCE_CUBES).innerHTML = this.format_block('jstpl_city_banner', {city: city, city_name: this.getCityNameTr(city)});
            // readd listeners to chosen city
            const civ_mils = $(city+'_military_'+this.player_id).getElementsByClassName('prk_military');
            [...civ_mils].forEach(ctr => this.makeSelectable(ctr));
        },

        /**
         * Clear "committed" gamedatas
         */
        onResetForces: function() {
            this.gamedatas.gamestate.args['committed'] = {};
            this.setDescriptionOnMyTurn(_("You must commit forces"));

            const mils = $('mymilitary').getElementsByClassName('prk_military ');
            [...mils].forEach(m => {
                // redisplay counters that were hidden before
                m.style.display = "block";
                // reenable deselected counters
                if (m.getAttribute("data-selectable") == "false") {
                    this.makeSelectable(m);
                }
            });
            this.toggleAssignmentCancelButton(false);
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( this.player_id + ' Entering state: '+stateName );
            
            switch( stateName ) {
                case 'choosePlaceInfluence':
                    if( this.isCurrentPlayerActive() ) {
                        let cities = document.getElementsByClassName("prk_city");
                        [...cities].forEach(c => c.classList.add("prk_city_active"));
                    }
                    break;
                case 'proposeCandidates':
                    if (this.isCurrentPlayerActive()) {
                        for (const city of CITIES) {
                            const candidate_space = this.openCandidateSpace(city);
                            if (candidate_space && this.hasCubeInCity(city, true)) {
                                const city_div = $(city);
                                city_div.classList.add("prk_city_active");
                                candidate_space.classList.add("prk_candidate_space_active");
                            }
                        }
                    }
                    break;
                case 'assassinate':
                    if (this.isCurrentPlayerActive()) {
                        let cubes = document.getElementsByClassName("prk_cube");
                        [...cubes].forEach( c => c.classList.add("prk_cubes_remove"));
                    }
                    break;
                case 'commitForces':
                    if (this.isCurrentPlayerActive()) {
                        const mils = $('mymilitary').getElementsByClassName("prk_military");
                        [...mils].forEach(m => {
                            this.makeSelectable(m);
                        });
                        this.gamedatas.gamestate.args = {};
                        this.gamedatas.gamestate.args.committed = {};
                    }
                    const battleslots = $('location_area').getElementsByClassName("prk_battle");
                    [...battleslots].forEach(b => {
                        this.makeSplayable(b);
                    });

                    break;
                case 'dummmy':
                    break;
            }
            if (MILITARY_DISPLAY_STATES.includes(stateName)) {
                $('military_board').style['display'] = 'block';
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName ) {
                case 'choosePlaceInfluence':
                    this.stripClassName("prk_city_active");
                    break;
                case 'proposeCandidates':
                    this.stripClassName("prk_city_active");
                    this.stripClassName("prk_candidate_space_active");
                    this.stripClassName("prk_cubes_active");
                    break;
                case 'assassinate':
                    this.stripClassName("prk_cubes_remove");
                    break;
                case 'commitForces':
                    const mils = $('mymilitary').getElementsByClassName("prk_military");
                    [...mils].forEach(m => {
                        this.makeSelectable(m, false);
                    });
                    this.gamedatas.gamestate.args = {};
                    this.gamedatas.gamestate.args.committed = {};
                    break;
                case 'dummmy':
                    break;
            }
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {
                switch( stateName ) {
                    case 'spartanChoice':
                        for (player_id in this.gamedatas.players) {
                            this.addActionButton( 'choose_'+player_id, this.decorator.spanPlayerName(player_id), 'choosePlayer', null, false, 'gray' );
                        }
                        break;
                    case 'commitForces':
                        this.addActionButton( "commit_send_btn", _('Commit Forces'), () => {
                            this.onCommitForces();
                        });
                        // add Cancel button if some units have already been assigned
                        this.addActionButton( "commit_cancel_btn", _('Cancel'), () => {
                            this.onResetForces();
                        }, null, null, 'red');
                        this.toggleAssignmentCancelButton(false);
                        break;
                    case 'specialTile':
                        this.addSpecialPassButton();
                        break;
                }
            }
            // buttons that can be added even for non-current player
            switch( stateName ) {
                case 'takeInfluence':
                case 'specialTile':
                case 'commitForces':
                        if (args._private.special) {
                        const buttonlbl = this.getSpecialButtonLabel(this.player_id);
                        this.addActionButton( 'play_special_btn', buttonlbl, () => {
                            this.specialTileWrapper();
                        }, null, false, 'blue' );
                    }
                    break;
        }

        },

        ///////////////////////////////////////////////////
        //// Handling Special Tiles
        ///////////////////////////////////////////////////

        /**
         * Get the translatable label for a Special Tile button
         * @param {string} player_id 
         * @returns "Use ${special} tile"
         */
         getSpecialButtonLabel: function(player_id) {
            let buttonlbl = _("Use ${special_name} tile");
            const speciallbl = this.getPlayerSpecial(player_id);
            const specialtr = this.getSpecialTileTr(speciallbl);
            buttonlbl = buttonlbl.replace('${special_name}', specialtr);
            return buttonlbl;
        },

        /**
         * Get the translated a player's Special Tile label.
         * @param {string} player_id 
         * @returns label.
         */
        getPlayerSpecial: function(player_id) {
            const mycards = $(player_id+"_player_cards");
            const myspecial = mycards.getElementsByClassName("prk_special_tile")[0];
            for (const s of SPECIAL_TILES) {
                if (myspecial.classList.contains(s)) {
                    return s;
                }
            }
            return null;
        },

        /**
         * Check cards before submitting to the specialTile function.
         */
         specialTileWrapper: function() {
            const special = this.getPlayerSpecial(this.player_id);
            if (special == "plague") {
                this.addPlagueButtons();
            } else if(special == "alkibiades") {
                this.addAlkibiadesButtons();
            } else if(special == "slaverevolt") {
                this.addSlaveRevoltButtons();
            } else {
                this.specialTile(true);
            }
        },

        /**
         * When Special tile is canceled, re-add it.
         * Also add the Pass button if it's the Special Tile phase.
         * @param {string} special
         */
         addSpecialTileCancel: function(special) {
            this.addActionButton( special+"_cancel_btn", _("Cancel"), () => {
                this.restoreDescriptionOnMyTurn();
                this.removeActionButtons();
                this.addActionButton( 'play_special_btn', this.getSpecialButtonLabel(this.player_id), () => {
                    this.specialTileWrapper();
                }, null, false, 'blue' );
                if (this.gamedatas.gamestate.name == "specialTile") {
                    this.addSpecialPassButton();
                }
            }, null, null, 'red');
        },

        /**
         * Add the 'Pass' button to pass on playing a Special tile.
         */
        addSpecialPassButton: function() {
            this.addActionButton( 'pass_btn', _("Pass"), () => {
                this.specialTile(false);
            }, null, false, 'red' );
        },

        /////////////////////// PLAGUE ///////////////////////

        /**
         * Player clicked "Use Plague" button.
         */
        addPlagueButtons: function() {
            this.setDescriptionOnMyTurn(_("Select a city to be struck with plague"), {'plague': true});
            this.removeActionButtons();
            const plaguebuttons = document.getElementsByClassName("prk_plague_btn");
            [...plaguebuttons].forEach( p => p.addEventListener('click', () => {
                const city = p.id.split("_")[0];
                this.onPlagueCity(city);
            }));

            this.addSpecialTileCancel("plague");
        },

        /////////////////////// ALKIBIADES ///////////////////////

        /**
         * Player clicked "Use Alkibiades" button.
         */
         addAlkibiadesButtons: function() {
            this.setDescriptionOnMyTurn(_("Select 2 cubes to move"), {'alkibiades': true});
            this.removeActionButtons();

            // add listeners to the cubes in the From cities
            const alkibiadescubes = $('alkibiades_from_cities').getElementsByClassName('prk_cube');
            [...alkibiadescubes].forEach( c => this.addAlkibiadesCubesEventListeners(c));

            // add listeners to the To-buttons
            const to_civs = $('alkibiades_to_cities').getElementsByClassName('prk_alkibiades_btn');
            [...to_civs].forEach(civ => this.addAlkibiadesToCivListeners(civ));

            this.addActionButton( 'alkibiades_move_btn', _("Confirm"), () => {
                this.onAlkibiadesMove();
            }, null, false, 'blue' );
            $('alkibiades_move_btn').classList.add('disabled');
            this.addSpecialTileCancel("alkibiades");
        },

        /**
         * Add listeners to cubes in the Alkibiades banner.
         * Makes them light up and spin when hovered
         * @param {Object} cube 
         */
         addAlkibiadesCubesEventListeners: function(cube) {
            cube.classList.add("prk_cube_alkibiades");
            // spin and highlight
            cube.addEventListener('mouseenter', () => {
                const cubes = this.getAlkibiadesCubesToMove();
                if (cubes.length < 2) {
                    cube.classList.add("prk_cube_alkibiades_active");
                }
            });
            // unhighlight
            cube.addEventListener('mouseleave', () => {
                cube.classList.remove("prk_cube_alkibiades_active");
            });
            cube.addEventListener('click', () => {
                const cubes = this.getAlkibiadesCubesToMove();
                if (cubes.length < 2) {
                    // unmark any previous cube
                    this.deselectAlkibiadesCube();
                    // mark the cube
                    cube.classList.add('prk_alkibiades_selected');
                    // highlight the To box with the selected player's color
                    const [selected_pid, fromcity] = cube.id.split("_").splice(0, 2);
                    this.decorateAlkibiadesToDiv(selected_pid, fromcity);
                }
            });
        },

        /**
         * Add listeners to the To buttons for Alkibiades
         * @param {Object} civ 
         */
        addAlkibiadesToCivListeners: function(civ) {
            // colored when hovered
            civ.addEventListener('mouseenter', () => this.enterCivBtnAlkibiades(civ));
            // uncolor when left
            civ.addEventListener('mouseleave', () => this.leaveCivBtnAlkibiades(civ));
            // clicking a Civ to place cube there
            civ.addEventListener('click', () => this.clickCivBtnAlkibiades(civ));
        },

        /**
         * Highlight the Alkibiades "To" div with player cube color.
         * @param {string} player_id
         * @param {string} fromcity
         */
        decorateAlkibiadesToDiv: function(player_id, fromcity) {
            let pcolor = this.decorator.playerColor(player_id);
            if (pcolor == 'white') {
                pcolor = 'gray';
            }
            const to_city_container = $('alkibiades_to_cities');
            to_city_container.style['box-shadow'] = '2px 2px 15px 5px '+pcolor;
            const toButtons = to_city_container.getElementsByClassName('prk_alkibiades_btn ');
            // remove any previously disabled toCiv marks
            [...toButtons].forEach(tb => tb.classList.remove('prk_alkibiades_civ_noselect'));
            $(fromcity+"_alkibiades_to_btn").classList.add('prk_alkibiades_civ_noselect');
        },

        /**
         * When entering one of the Alkibiades To buttons, color it if it's not the from city.
         * @param {element} tociv 
         */
        enterCivBtnAlkibiades: function(tociv) {
            // get the selected cube
            const selected = this.getAlkibiadesCubeSelected();
            if (selected) {
                const fromcity = selected.id.split("_")[1];
                const tocity = tociv.id.split("_")[0];
                if (fromcity != tocity) {
                    Object.assign(tociv.style, {
                        'background-color': 'var(--color_'+tocity+')',
                        'cursor': 'grab'
                    });
                }
            }
        },

        /**
         * Uncolor Alkibiades To button.
         * @param {element} tociv 
         */
         leaveCivBtnAlkibiades: function(tociv) {
             if (!tociv.classList.contains('prk_alkibiades_civ_noselect')) {
                Object.assign(tociv.style, {
                    'background-color': 'white',
                    'cursor': 'default'
                });
             }
        },

        /**
         * Dropping a cube on a city to move it there.
         * @param {element} tociv
         */
         clickCivBtnAlkibiades: function(tociv) {
             if (tociv.classList.contains('prk_alkibiades_civ_noselect')) {
                 return;
             }
            const selected = this.getAlkibiadesCubeSelected();
            if (selected) {
                const [player_id, fromcity] = selected.id.split("_").splice(0,2);
                const tocity = tociv.id.split("_")[0];
                const previouscubes = this.getAlkibiadesCubesToMove();
                // how many have already been put down? Should be 0 or 1
                const movedcubes = previouscubes.length;
                if (movedcubes < 2) {
                    if (fromcity != tocity) {
                        const cubehtml = this.createInfluenceCube(player_id, fromcity, 'move'+(movedcubes+1));
                        dojo.place(cubehtml, tociv);
                        this.deselectAlkibiadesCube();
                    }
                    let movestr = _("Move one of ${player_name}'s cubes from ${from_city} to ${to_city}");
                    const from_city_name = this.spanCityName(fromcity);
                    const to_city_name = this.spanCityName(tocity);
                    // const cube = this.createInfluenceCube(player_id, fromcity, 'banner');
                    movestr = movestr.replace('${player_name}', this.decorator.spanPlayerName(player_id));
                    movestr = movestr.replace('${from_city}', from_city_name);
                    movestr = movestr.replace('${to_city}', to_city_name);
                    // movestr = movestr.replace('${cube}', cube);
                    $('alkibiades_selections').innerHTML += (movedcubes == 0 ? '' : '<br/>') + movestr;
                    if (movedcubes == 0) {
                        // if this was the first cube, and it was the only cube that player had in the city, take it off the Alkibiades banner
                        const remainingcubes = $(fromcity+'_cubes_'+player_id).getElementsByClassName("prk_cube");
                        if (remainingcubes.length < 2) {
                            $(player_id+'_'+fromcity+'_alkibiades').remove();
                        }
                    } else {
                        // if this was the second, then activate submit button
                        $('alkibiades_move_btn').classList.remove('disabled');
                    }
                } else {
                    throw new Error("Too many cubes selected!");
                }
            }
        },

        /**
         * Get all the cubes currently on the To-Move box
         * @returns alkibades cube array
         */
        getAlkibiadesCubesToMove: function() {
            alkcubes = [];
            for (const city of CITIES) {
                const citybtn = $(city+'_alkibiades_to_btn');
                const cubes = citybtn.getElementsByClassName('prk_cube');
                [...cubes].forEach(c => {
                    const alk = new perikles.alkibiades();
                    alk.setToCity(city);
                    alk.setValues(c);
                    alkcubes.push(alk);
                });
            }
            return alkcubes;
        },

        /**
         * Get the Alkibiades cube that was clicked to be moved.
         * @return a selected cube or null
         */
        getAlkibiadesCubeSelected: function() {
            const selected = $('alkibiades_from_cities').getElementsByClassName('prk_alkibiades_selected');
            if (selected.length > 1) {
                throw new Error("Multiple Alkibiades cubes have been marked as selected");
            }
            if (selected.length == 0) {
                return null;
            } else {
                return selected[0];
            }
        },

        /**
         * Cube selected for Alkibiades movement is unselected.
         */
        deselectAlkibiadesCube: function() {
            const to_city_container = $('alkibiades_to_cities');
            to_city_container.style['box-shadow'] = '';
            const toButtons = to_city_container.getElementsByClassName('prk_alkibiades_btn');
            // remove any previously disabled toCiv marks
            [...toButtons].forEach(tb => tb.classList.remove('prk_alkibiades_civ_noselect'));
            const fromCubes = $('alkibiades_from_cities').getElementsByClassName('prk_cube_alkibiades');
            [...fromCubes].forEach(c => c.classList.remove('prk_alkibiades_selected'));
        },

        /////////////////////// SLAVE REVOLT ///////////////////////

        /**
         * Player clicked "Use Slave Revolt" button
         */
        addSlaveRevoltButtons: function() {
            this.setDescriptionOnMyTurn(_("Choose location for Slave Revolt (one Spartan Hoplite counter will be removed"), {'slaverevolt': true});
            this.removeActionButtons();
            const srbtns = $('slaverevolt_div').getElementsByClassName("prk_slaverevolt_btn");
            [...srbtns].forEach(b => this.addSlaveRevoltListeners(b));

            this.addSpecialTileCancel("slaverevolt");
        },

        /**
         * 
         * @param {Object} button 
         */
        addSlaveRevoltListeners: function(button) {
            // clicking a Civ to place cube there
            button.addEventListener('click', () => {
                const id = button.id.split("_")[0];
                this.onSlaveRevolt(id);
            });
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /**
         * For a city, get the current player who's leader.
         * @param {string} city 
         * @return player_id (null if no one controls that city)
         */
        getLeader: function(city) {
            for (const player_id in this.gamedatas.players) {
                if (this.isLeader(player_id, city)) {
                    return player_id;
                }
            }
            return null;
        },

        /**
         * Is this player leader of this city?
         * @param {string} player_id 
         * @param {string} city 
         */
        isLeader: function(player_id, city) {
            let isLeader = false;
            const leader = $(city+'_leader').firstChild;
            if (leader) {
                const color = this.decorator.playerColor(player_id);
                if (leader.classList.contains('prk_leader_'+color)) {
                    isLeader = true;
                }
            }
            return isLeader;
        },

        /**
         * Does the player have any uncommitted military units in that city?
         * @param {string} player_id 
         * @param {string} city 
         */
        hasAvailableUnits: function(player_id, city) {
            for (const u of [HOPLITE, TRIREME]) {
                if ($(city+'_'+u+'_'+player_id).childElementCount > 0) {
                    return true;
                }
            };
            return false;
        },

        /**
         * Does current player have a cube in the city?
         * @param {string} city 
         * @param {bool} candidates including candidates? default: false
         * @returns true if this player has a cube in city
         */
        hasCubeInCity: function(city, candidates=false) {
            const player_id = this.player_id;
            if (candidates) {
                if (document.getElementById(player_id+"_"+city+"_a")) {
                    return true;
                }
                if (document.getElementById(player_id+"_"+city+"_b")) {
                    return true;
                }
            }
            if ($(city+"_cubes_"+player_id).getElementsByClassName("prk_cube").length > 0) {
                return true;
            }
            return false;
        },

        /**
         * For a city, returns the div for candidate a if it's empty, else b if it's empty, else null
         * @param {string} city 
         * @returns a DOM Element or else null
         */
         openCandidateSpace: function(city) {
            let candidate_space = null;
            const citya = $(city+"_a");
            if (!citya.hasChildNodes()) {
                candidate_space = citya;
            } else {
                const cityb = $(city+"_b");
                if (!cityb.hasChildNodes()) {
                    candidate_space = cityb; 
                }
            }
            return candidate_space;
        },

        /**
         * Strip all elements of the document of a given class name
         * @param {string} cls className
         */
        stripClassName: function(cls) {
            let actdiv = document.getElementsByClassName(cls);
            [...actdiv].forEach( a => a.classList.remove(cls));
        },

        /**
         * Remove the bottommost of a player's Influence cubes in a city
         * @param {string} player_id 
         * @param {string} city
         * @param {int} num
         */
        removeInfluenceCubes: function(player_id, city, num) {
            const from_div = $(city+'_cubes_'+player_id);
            const cubes = from_div.children;
            for (let i = 0; i < num; i++) {
                const toremove = cubes[i];
                this.fadeOutAndDestroy(toremove, 500);
            }
        },

        /**
         * For military counters, makes them selectable/unselectable
         * @param {DOM} counter 
         * @param {bool} selectable (default true)
         */
        makeSelectable: function(counter, selectable=true) {
            counter.setAttribute("data-selectable", selectable);
            counter.style.outline = selectable ? "3px red dashed" : null;
            if (selectable) {
                this.connect(counter, 'mouseenter', this.stacks.hoverUnit);
                this.connect(counter, 'mouseleave', this.stacks.unhoverUnit);
                this.connect(counter, 'click', this.assignUnit.bind(this));
            } else {
                this.disconnect(counter, 'mouseenter');
                this.disconnect(counter, 'mouseleave');
                this.disconnect(counter, 'click');
            }
        },

        /**
         * For places to stack units at battles.
         * @param {DOM} battleslot 
         * @param {bool} splay 
         */
        makeSplayable: function(battleslot, splay=true) {
            if (splay) {
                this.connect(battleslot, 'click', this.stacks.splayUnits);
                this.connect(battleslot, 'mouseenter', this.stacks.splayUnits);
                this.connect(battleslot, 'mouseleave', this.stacks.unsplayUnits);
            } else {
                this.disconnect(battleslot, 'click', this.stacks.splayUnits);
                this.disconnect(battleslot, 'mouseenter', this.stacks.splayUnits);
                this.disconnect(battleslot, 'mouseleave', this.stacks.unsplayUnits);
            }
        },

        /**
         * Button to assign a unit to a battle.
         * @param {Object} evt event
         */
        assignUnit: function(evt) {
            const selectedUnit = evt.currentTarget;

            this.commitDlg = new ebg.popindialog();
            this.commitDlg.create( 'commitDlg' );
            const [city,unit,strength,id] = selectedUnit.id.split('_');
            const counter = new perikles.counter(city,unit,strength,id);

            const unit_str = this.counterSpan(counter);
            const unitc = counter.copy();

            this.commitDlg.setTitle( _("Commit Forces") );
            this.commitDlg.setMaxWidth( 720 );
            const html = '<div id="CommitDialogDiv" style="display: flex; flex-direction: column; top: 50px;">\
                            <div style="display: flex; flex-direction: row; align-items: center;">'
                            +unitc + this.createLocationTileIcons(city)+
                            '</div>\
                            <div id="commit_text" style="margin: 2px; padding: 2px; text-align: center; color: #fff; background-color: #4992D2; display: none;"></div>\
                            <div style="display: flex; flex-direction: row; justify-content: space-evenly;">\
                                <div id="send_button" class="prk_btn prk_send_btn">'+_("Assign Unit")+'</div>\
                                <div id="cancel_button" class="prk_btn prk_cancel_btn">'+_("Cancel")+'</div>\
                            </div>\
                        </div>';
            // Show the dialog
            this.commitDlg.setContent( html );
            this.commitDlg.show();
            this.commitDlg.hideCloseIcon();
            const dlg = $('CommitDialogDiv');
            dlg.onclick = event => {
                const target = event.target;

                const attack_str = _("Send ${unit} to attack ${location}?");
                const defend_str = _("Send ${unit} to defend ${location}?");
                let banner_txt = null;

                if (target.id == "send_button" ) {
                    let location = dlg.getAttribute("data-location");
                    let sendto = dlg.getAttribute("data-side");
                    if (location && sendto) {
                        this.onSendUnit(id, city, unit, strength, sendto, location);
                        this.commitDlg.destroy();
                    } else {
                        banner_txt = '<span style="color: white; font-size: larger; font-weight: bold;">'+_("You must select a location")+'</span>';
                    }
                } else if (target.id == "cancel_button") {
                    this.commitDlg.destroy();
                } else if (target.classList.contains("prk_battle_icon")) {
                    const [side, loc] = target.id.split('_');
                    dlg.setAttribute("data-location", loc);
                    dlg.setAttribute("data-side", side);
                    banner_txt = side == "attack" ? attack_str : defend_str;
                    banner_txt = banner_txt.replace('${location}', '<span style="color: var(--color_'+LOCATION_TILES[loc].city +');">'+this.getBattleNameTr(loc)+'</span>');
                    banner_txt = banner_txt.replace('${unit}', unit_str);
                }
                if (banner_txt) {
                    $(commit_text).style.display = "block";
                    $(commit_text).innerHTML = banner_txt;
                }
            };
            this.toggleAssignmentCancelButton(true);
        },

        /**
         * Check whether player can attack a city
         * @param {string} city 
         * @returns true if it's okay to attack
         */
        canAttack: function(city) {
            let can_attack = true;
            if (this.isLeader(this.player_id, city)) {
                can_attack = false;
            }
            return can_attack;
        },

        /**
         * Toggle the "Cancel" button for unit assignments.
         * @param {bool} enable turn it on or off
         */
        toggleAssignmentCancelButton: function(enable) {
            if (enable) {
                $('commit_cancel_btn').classList.remove('disabled');
                $('commit_cancel_btn').style['display'] = 'inline';
            } else {
                $('commit_cancel_btn').classList.add('disabled');
                $('commit_cancel_btn').style['display'] = 'none';
            }
        },

        ///////////////////////////////////////////////////
        //// Component creation - create HTML elements

        /**
         * Create buttons to spend a city influence cube to send more units.
         * @returns html for cubes for cities I own; null if none possible
         */
        createSpendInfluenceDiv: function() {
            let html = null;
            let canSpend = false;
            let civ_btns = "";
            for (const city of CITIES) {
                if (this.isLeader(this.player_id, city)) {
                    //any cubes left?
                    if (this.hasCubeInCity(city) && this.hasAvailableUnits(this.player_id, city)) {
                        civ_btns += this.format_block('jstpl_city_btn', {city: city, city_name: this.getCityNameTr(city)});
                        canSpend = true;
                    }
                }
            }
            if (canSpend) {
                html = '<div id="'+COMMIT_INFLUENCE_CUBES+'" style="display: inline-flex; flex-direction: row;">';
                html += civ_btns;
                html += '</div>';
            }
            return html;
        },

        /**
         * Create the div containing all the location tiles that go in a Commit Forces dialog.
         * @param unit_city city the unit is from
         * @returns html
         */
        createLocationTileIcons: function(unit_city) {
            let loc_html = '<div style="display: flex; flex-direction: column; margin: 10px;">';
            const location_tiles = $('location_area').getElementsByClassName("prk_location_tile");
            [...location_tiles].forEach(loc => {
                loc_html += '<div style="display: flex; flex-direction: row; align-items: center;">';
                const battle = loc.id.split('_')[0];
                // can't attack own city
                const battle_city = LOCATION_TILES[battle].city;
                if (unit_city != battle_city && this.canAttack(battle_city)) {
                    loc_html += '<div id="attack_'+battle+'" class="prk_battle_icon prk_sword"></div>';
                } else {
                    loc_html += '<div class="prk_blank_icon"></div>';
                }
                const loc_tile = this.createLocationTile(battle, 1);
                loc_html += loc_tile;
                loc_html += '<div id="defend_'+battle+'" class="prk_battle_icon prk_shield"></div>';
                loc_html += '</div>';
            });
            loc_html += '</div>';
            return loc_html;
        },

        /**
         * Create buttons to afflict city with plague
         * @returns html for plague buttons
         */
         createPlagueButtons: function() {
            let html = '<div id="plague_city_div">';
            for (const city of CITIES) {
                html += this.format_block('jstpl_plague_btn', {city: city, city_name: this.getCityNameTr(city)});
            }
            html += '</div>';
            return html;
        },

        /**
         * Create buttons to move cubes with Alkibiades.
         */
        createAlkibiadesButtons: function() {
            let fromcivs = '';
            let tocivs = '';
            for (const city of CITIES) {
                fromcivs += '<div class="prk_alkibiades_row">';
                tocivs += '<div class="prk_alkibiades_row">';
                fromcivs += this.format_block('jstpl_alkibiades_from_btn', {city: city, city_name: this.getCityNameTr(city)});
                tocivs += this.format_block('jstpl_alkibiades_to_btn', {city: city, city_name: this.getCityNameTr(city)});
                for (player_id in this.gamedatas.players) {
                    const cubes_div = $(city+"_cubes_"+player_id);
                    const cubes = cubes_div.childElementCount;
                    if (cubes > 0) {
                        const cube = this.createInfluenceCube(player_id, city, 'alkibiades');
                        fromcivs += cube;
                    }
                }
                fromcivs += '</div>';
                tocivs += '</div>';
            }

            let html = '<br/><div id="alkibiades_from_cities" class="prk_alkibiades_civs">';
            html += '<h2>'+_('From')+'</h2>';
            html += fromcivs;
            html += '</div>';
            html += '<div id="alkibiades_to_cities" class="prk_alkibiades_civs">';
            html += '<h2>'+_('To')+'</h2>';
            html += tocivs;
            html += '</div><br/>';
            html += '<div id="alkibiades_selections" class="prk_alkibiades_banner"></div>';
            return html;
        },

        /**
         * Create buttons for choosing Slave Revolt location.
         * Controlling player+Battle location tiles
         * @returns html
         */
        createSlaveRevoltButtons: function() {
            let html = '<div id="slaverevolt_div">';
            const sparta_leader = this.getLeader("sparta");
            if (sparta_leader) {
                const spartabtn = this.slaverevolt.createSpartaLeaderButton(this.decorator.spanPlayerName(sparta_leader));
                html += spartabtn;
                const locations = this.slaverevolt.getSpartanHopliteLocations();
                for (const stack of locations) {
                    const battle = this.getBattleNameTr(stack.tile);
                    const locbtn = this.slaverevolt.createButton(stack.tile, battle);
                    html += locbtn;
                }
            } else {
                html += '<span>'+_("Sparta has no leader; no revolt possible")+'</span>';
            }
            html += '</div>';
            return html;
        },

        /**
         * Get the "city unit-strength" label for a counter.
         * @param {Object} counter 
         * @returns span html
         */
         counterSpan: function(counter) {
            let unit_str = _("${city_name} ${unit}-${strength}");
            unit_str = unit_str.replace('${city_name}', '<span style="color: var(--color_'+counter.getCity()+');")>'+this.getCityNameTr(counter.getCity())+'</span>');
            unit_str = unit_str.replace('${unit}', '<b>${unit}</b>');
            unit_str = unit_str.replace('${unit}', counter.getType() == HOPLITE ? _("Hoplite") : _("Trireme"));
            unit_str = unit_str.replace('${strength}', counter.getStrength());
            return unit_str;
        },

        /**
         * Get the "city unit-strength" label for a counter.
         * @param {Object} counter 
         * @returns plain text for title
         */
        counterText: function(counter) {
            let unit_str = _("${city_name} ${unit}-${strength}");
            unit_str = unit_str.replace('${city_name}', this.getCityNameTr(counter.getCity()));
            unit_str = unit_str.replace('${unit}', counter.getType() == HOPLITE ? _("Hoplite") : _("Trireme"));
            unit_str = unit_str.replace('${strength}', counter.getStrength());
            return unit_str;
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /**
         * Action to take an Influence card.
         * @param {string} id
         */
         takeInfluenceTile: function(card_id) {
            if (this.checkAction("takeInfluence", true)) {
                const id = parseInt(card_id.match(/\d+$/)[0]);
                this.ajaxcall( "/perikles/perikles/takeinfluence.html", { 
                    id: id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to place an Influence cube on a city.
         * @param {string} city 
         */
        placeInfluenceCube: function(city) {
            if (this.checkAction("placeAnyCube", true)) {
                this.ajaxcall( "/perikles/perikles/placecube.html", { 
                    city: city,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to assign a Candidate to a city from a player
         * @param {string} city 
         * @param {string} player_id 
         */
        proposeCandidate: function(city, player_id) {
            if (this.checkAction("proposeCandidate", true)) {
                this.ajaxcall( "/perikles/perikles/selectcandidate.html", { 
                    city: city,
                    player: player_id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to remove a cube.
         * @param {string} player_id 
         * @param {string} city 
         * @param {string} c 
         */
        removeCube: function(player_id, city, c) {
            if (this.checkAction("chooseRemoveCube", true)) {
                this.ajaxcall( "/perikles/perikles/removecube.html", { 
                    player: player_id,
                    city: city,
                    cube: c,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to choose next player.
         * @param {Object} event 
         */
        choosePlayer: function(event) {
            if (this.checkAction("chooseNextPlayer", true)) {
                const player_id = event.currentTarget.id.split("choose_")[1];
                this.ajaxcall( "/perikles/perikles/chooseplayer.html", { 
                    player: player_id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }

        },

        /**
         * Action to send forces
         */
        commitForces: function() {
            if (this.checkAction("assignUnits", true)) {
                let cube = this.gamedatas.gamestate.args.committed['cube'] ?? "";
                let units = this.packCommitForcesArg(this.gamedatas.gamestate.args.committed);
                this.ajaxcall( "/perikles/perikles/commitUnits.html", { 
                    units: units,
                    cube: cube,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Player plays or declines to play Special Tile.
         * @param {bool} use
         */
        specialTile: function(bUse) {
            if (this.checkPossibleActions("useSpecialTile", true)) {
                this.ajaxcall( "/perikles/perikles/specialTile.html", {
                    player: this.player_id,
                    use: bUse,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * Player clicked a City to Plague.
         * @param {string} city 
         */
        onPlagueCity: function(city) {
            if (this.checkPossibleActions("useSpecialTile", true)) {
                this.ajaxcall( "/perikles/perikles/plague.html", { 
                    city: city,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * Player clicked Confirm on Alkibiades.
         */
        onAlkibiadesMove: function() {
            if (this.checkPossibleActions("useSpecialTile", true)) {
                const cubes = this.getAlkibiadesCubesToMove();
                if (cubes.length == 2) {
                    this.ajaxcall( "/perikles/perikles/alkibiades.html", {
                        player1: cubes[0].player(),
                        player2: cubes[1].player(),
                        from1: cubes[0].from(),
                        from2: cubes[1].from(),
                        to1: cubes[0].to(),
                        to2: cubes[1].to(),
                        lock: true
                    }, this, function( result ) {  }, function( is_error) { } );
                    this.restoreDescriptionOnMyTurn();
                } else {
                    throw new Error("Two cubes must be selected!");
                }
            }
        },

        /**
         * Player clicked a Slave Revolt button.
         * @param {string} loc sparta or battle location
         */
        onSlaveRevolt: function(loc) {
            if (this.checkPossibleActions("useSpecialTile", true)) {
                this.ajaxcall( "/perikles/perikles/slaverevolt.html", {
                    location: loc,
                    loc: true
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * Pack all units being sent into a space-delimited string "id_attdef_location"
         * @param {Object} committed 
         */
        packCommitForcesArg: function(committed) {
            let argstr = "";
            for (const[id, selected] of Object.entries(committed)) {
                if (id != "cube") {
                    let location = selected.location;
                    let side = selected.side;
                    argstr += id+"_"+side+"_"+location+" ";
                }
            }
            return argstr;
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your perikles.game.php file.
        
        */
        setupNotifications: function() {
            dojo.subscribe( 'influenceCardTaken', this, "notif_influenceCardTaken" );
            this.notifqueue.setSynchronous( 'influenceCardTaken', 1000 );
            dojo.subscribe( 'influenceCubes', this, "notif_addInfluenceCubes");
            this.notifqueue.setSynchronous( 'influenceCubes', 500 );
            dojo.subscribe( 'candidateProposed', this, "notif_candidateProposed");
            this.notifqueue.setSynchronous( 'candidateProposed', 1000 );
            dojo.subscribe( 'cubeRemoved', this, "notif_cubeRemoved");
            this.notifqueue.setSynchronous( 'cubeRemoved', 1000 );
            dojo.subscribe( 'candidatePromoted', this, "notif_candidatePromoted");
            this.notifqueue.setSynchronous( 'candidatePromoted', 1000 );
            dojo.subscribe( 'influenceCardDrawn', this, "notif_influenceCardDrawn");
            this.notifqueue.setSynchronous( 'influenceCardDrawn', 1000 );
            dojo.subscribe( 'election', this, "notif_election");
            this.notifqueue.setSynchronous( 'election', 1000 );
            dojo.subscribe( 'takeMilitary', this, "notif_takeMilitary");
            this.notifqueue.setSynchronous( 'election', 500 );
            dojo.subscribe( 'useTile', this, "notif_useTile");
            this.notifqueue.setSynchronous( 'useTile', 500 );
            dojo.subscribe( 'spentInfluence', this, "notif_cubeRemoved");
            this.notifqueue.setSynchronous( 'spentInfluence', 500 );
            dojo.subscribe( 'sendMilitary', this, "notif_sendBattle");
            this.notifqueue.setSynchronous( 'sendMilitary', 1000 );
            dojo.subscribe( 'newInfluence', this, "notif_newInfluence");
            dojo.subscribe( 'newLocations', this, "notif_newLocations");
            dojo.subscribe( 'unclaimedTile', this, "notif_unclaimedTile");
            dojo.subscribe( 'returnMilitary', this, "notif_returnMilitary");
            dojo.subscribe( 'playSpecial', this, "notif_playSpecial");
            this.notifqueue.setSynchronous( 'notif_playSpecial', 500 );
            dojo.subscribe( 'alkibiadesMove', this, "notif_alkibiadesMove");
            this.notifqueue.setSynchronous( 'notif_alkibiadesMove', 500 );

            dojo.subscribe( 'revealCounters', this, "notif_revealCounters");
            dojo.subscribe( 'slaveRevolt', this, "notif_slaveRevolt");

            dojo.subscribe( 'battle', this, "notif_battle");
            dojo.subscribe( 'crtOdds', this, "notif_crtOdds");
            this.notifqueue.setSynchronous( 'crtOdds', 500 );
            dojo.subscribe( 'diceRoll', this, "notif_diceRoll");
            dojo.subscribe( 'resetBattleTokens', this, "notif_resetBattleTokens");
            
        },

        // Notification handlers

        /**
         * When Influence cubes are added to a city. Animate cubes from card to player's cube area in city.
         * @param {Object} notif 
         */
        notif_addInfluenceCubes: function( notif ) {
            const player_id = notif.args.player_id;
            const cubes = parseInt(notif.args.cubes);
            const city = notif.args.city;
            const from_div = $(player_id+'_player_cards');
            const to_div = $(city+'_cubes_'+player_id);
            const num = to_div.childElementCount;
            for (let c = 0; c < cubes; c++) {
                const i = num+c+1;
                const cube = this.createInfluenceCube(player_id, city, i);
                this.moveCube(cube, from_div, to_div, 1000+(c*500));
            }
        },

        /**
         * When an Influence tile is taken. Animate card going to player's board, and drawing a new one.
         * @param {Object} notif 
         */
        notif_influenceCardTaken: function( notif )
        {
            const player_id = notif.args.player_id;
            const city = notif.args.city;
            const id = notif.args.card_id;
            const card_id = city+'_'+id;
            const slot = notif.args.slot;

            // recreate the Influence tile
            const newTile = notif.args.tile;

            $(card_id).remove();
            const player_cards = player_id+'_player_cards';
            const from_id = "influence_slot_"+slot;

            // create temp new card to move to the player board
            const fromSlot = $(from_id);
            const newcard = this.createInfluenceCard(newTile);
            let newcard_div = dojo.place(newcard, fromSlot);
            this.slide(newcard_div, player_cards, {"from": fromSlot});
        },

        /**
         * Move card from deck to empty slot.
         * @param {Object} notif 
         */
         notif_influenceCardDrawn: function(notif) {
            // take top card off influence deck first
            this.removeTooltip( INFLUENCE_PILE );
            $(INFLUENCE_PILE).lastElementChild.remove();
            const decksize = $(INFLUENCE_PILE).childElementCount;

            // create the Influence tile
            const tile = notif.args.tile;
            const tile_div = this.createInfluenceCard(tile);
            const card = dojo.place(tile_div, $(INFLUENCE_PILE).lastElementChild);
            this.slideToObjectRelative( card.id, 'influence_slot_'+tile['slot'], 1000, 500 );
            this.decorateInfluenceCard(card, tile['city'], tile['type']);

            var pile_tt = _("Influence Deck: ${num} cards remaining");
            pile_tt = pile_tt.replace('${num}', decksize);
            this.addTooltip(INFLUENCE_PILE, pile_tt, '');

        },

        /**
         * A Candidate was proposed for a city, move it from nominee's cubes to space.
         * @param {Object} notif 
         */
        notif_candidateProposed: function(notif) {
            const player_id = notif.args.candidate_id;
            const city = notif.args.city;
            const candidate = notif.args.candidate; // alpha or beta
            const c = CANDIDATES[candidate]; // "a" or "b"
            const player_cubes = $(city+"_cubes_"+player_id);
            const cube1 = player_cubes.lastChild;

            const cube = this.createInfluenceCube(player_id, city, c);
            this.moveCube(cube, player_cubes, $(city+'_'+c), 500);
            this.fadeOutAndDestroy( cube1.id, 250);
            if (c == "a") {
                $(city+"_a").classList.remove("prk_candidate_space_active");
                $(city+"_b").classList.add("prk_candidate_space_active");
            } else {
                $(city+"_b").classList.remove("prk_candidate_space_active");
                $(city).classList.remove("prk_city_active")
            }
        },

        /**
         * A cube was removed - may be an Influence cube or a candidate
         * @param {Object} notif 
         */
        notif_cubeRemoved: function(notif) {
            const target_id = notif.args.candidate_id;
            const city = notif.args.city;
            const candidate = notif.args.candidate; // alpha or beta or null
            if (candidate) {
                const c = CANDIDATES[candidate]; // "a" or "b"
                this.fadeOutAndDestroy( target_id+"_"+city+"_"+c, 250);
            } else {
                const player_cubes = $(city+"_cubes_"+target_id);
                const cube1 = player_cubes.firstChild;
                this.fadeOutAndDestroy( cube1.id, 250);
            }
        },

        /**
         * Move cube from B to A
         * @param {Object} notif
         */
        notif_candidatePromoted: function(notif) {
            const city = notif.args.city;
            const candidate_id = notif.args.candidate_id;
            const fromcube = candidate_id+"_"+city+"_b";
            const to_div = $(city+"_a");
            this.slideToObjectRelative(fromcube, to_div, 1000, 1000, null, "last")
        },

        /**
         * Move a cube selected by Alkibiades.
         * @param {Object} notif 
         */
        notif_alkibiadesMove: function(notif) {
            const owner = notif.args.player_id;
            const fromcity = notif.args.city;
            const tocity = notif.args.city2;
            const player_cubes = $(fromcity+"_cubes_"+owner);
            const cube1 = player_cubes.firstChild;
            this.fadeOutAndDestroy( cube1.id, 250);

            const from_div = $(fromcity+'_cubes_'+owner);
            const to_div = $(tocity+'_cubes_'+owner);
            const i = to_div.childElementCount+1;
            const cube = this.createInfluenceCube(owner, tocity, i);
            this.moveCube(cube, from_div, to_div, 1000);
        },

        /**
         * Remove candidate cubes, place Leader counters.
         * @param {Object} notif 
         */
        notif_election: function(notif) {
            const player_id = notif.args.player_id;
            const city = notif.args.city;
            const cubes = parseInt(notif.args.cubes);
            // remove candidate cubes
            ["a", "b"].forEach(c => {
                if ($(city+"_"+c).hasChildNodes) {
                    const cand = $(city+"_"+c).lastElementChild;
                    this.fadeOutAndDestroy(cand.id, 500);
                }
            });
            // subtract loser's cubes from winner's
            this.removeInfluenceCubes(player_id, city, cubes);
            // place Leader
            this.createLeaderCounter(player_id, city, "leader", 1);
        },

        /**
         * Move military tokens to each Leader.
         * @param {Object} notif 
         */
        notif_takeMilitary: function(notif) {
            const military = notif.args.military;
            for (const mil of military) {
                this.counterToPlayerBoard(mil);
            }
        },

        /**
         * Send military units to battle tiles.
         * @param {Object} notif 
         */
        notif_sendBattle: function(notif) {
            const player_id = notif.args.player_id;
            const id = notif.args.id; // 0 if face-down
            const city = notif.args.city;
            const type = notif.args.type;
            const strength = notif.args.strength;
            const slot = notif.args.slot;
            const location = notif.args.location;
            const battlepos= notif.args.battlepos;
            const counter = new perikles.counter(city, type, strength, id, location, battlepos);
            this.moveToBattle(player_id, counter, slot);
        },

        /**
         * Move Influence tile to discard
         * @param {Object} notif 
         */
        notif_useTile: function(notif) {
            const city = notif.args.city;
            const id = notif.args.id;
            this.fadeOutAndDestroy(city+'_'+id, 2000, 0);
        },

        /**
         * Player played a special tile
         * @param {Object} notif 
         */
        notif_playSpecial: function(notif) {
            const player_id = notif.args.player_id;
            // get this player's special card
            const player_div = $(player_id+"_player_cards");
            const spec = player_div.getElementsByClassName("prk_special_tile")[0];
            spec.classList.remove("prk_special_tile_back");
            spec.classList.add("prk_special_tile_front", "prk_special_tile_used");
            if (this.player_id == player_id) {
                this.removeActionButtons();
            }
        },

        /**
         * Start of new turn, move all cards to Deck and deal new ones.
         * @param {Object} notif 
         */
        notif_newInfluence: function(notif) {
            const influencetiles = document.getElementsByClassName("prk_influence_tile");
            [...influencetiles].forEach(t => {
                this.slideToObjectAndDestroy(t, 'influence_slot_0', 500, 0);
            });
            const influence = notif.args.influence;
            const sz = parseInt(notif.args.decksize);
            this.setupInfluenceTiles(influence, sz);
        },

        /**
         * Start of new turn, deal out new locations.
         * @param {Object} notif 
         */
        notif_newLocations: function(notif) {
            const locations = notif.args.locations;
            this.setupLocationTiles(locations);
        },

        /**
         * When a Location tile is not claimed after a battle.
         * @param {Object} notif 
         */
        notif_unclaimedTile: function(notif) {
            const loc = notif.args.location;
            const tile = $(loc+'_tile');
            // clear margin before putting in box
            tile.style.margin = null;
            this.slideToObjectRelative(tile.id, 'unclaimed_tiles', 500, 0);
        },

        /**
         * Return military from a battle to cities
         * @param {Object} notif 
         */
        notif_returnMilitary: function(notif) {
            slot = notif.args.slot;
            const counters = $('battle_zone_'+slot).getElementsByClassName("prk_military");
            [...counters].forEach(c => {
                const counter_name = c.id;
                const [city, unit, strength, _, id] = counter_name.split('_');
                const city_military = city+"_military";
                this.slideToObjectAndDestroy(c, city_military, 1000, 500);
                new perikles.counter(city, unit, strength, id).addToStack();
            });
        },

        /**
         * Flip all the counters face up at a battle zone during the fight.
         * @param {Object} notif 
         */
        notif_revealCounters: function(notif) {
            const slot = notif.args.slot;
            const military = notif.args.military;
            // clear the old ones. TODO: animate flipping
            const oldcounters = $('battle_zone_'+slot).getElementsByClassName("prk_military");
            [...oldcounters].forEach(c => {
                c.remove();
            });
            let i = 0;
            military.forEach(m => {
                this.placeCounterAtBattle(m, i++);
            });
       },

       /**
        * A single Hoplite counter needs to be flipped and moved back to Sparta
        * @param {Object} notif 
        */
       notif_slaveRevolt: function(notif) {
            const counter = notif.args.military;
            const location = notif.args.location;
            const sparta_player = notif.args.sparta_player;
            const counter_id = "sparta_hoplite_"+counter.getStrength()+"_"+counter.id;
            debugger;
            
            if (location == "sparta") {
                // comes from player's pool
                if (this.player_id == sparta_leader) {
                } else {
                    
                }
            } else {
                // comes from a battle tile
                if (this.player_id == sparta_leader) {
                } else {
                    // flip a counter

                }
            }
            // now move it back to Sparta

        },

        notif_crtOdds: function(notif) {
            const slot = notif.args.slot;
            const crt = notif.args.crt;
            const crt_col = $('crt_'+crt);
            debugger;
            crt_col.classList.add("prk_crt_active");
        },

        /**
         * 
         * @param {Object} notif 
         */
        notif_diceRoll: function(notif) {

        },

        notif_resetBattleTokens: function(notif) {
            const tokens = document.getElementsByClassName("prk_battle_token");
            [...tokens].forEach(t => {
                t.remove();
            });
        },

        notif_battle: function(notif) {
            debugger;
            for (i = 0; i < 4; i++) {
                const token = '<div class="prk_battle_token"></div>';
                dojo.place(token, $('battle_tokens'));
            }
        },
   });
});
