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
const MILITARY_ROW = {'argos': 0, 'athens': 1, 'corinth': 2, 'megara': 3, 'sparta': 4, 'thebes': 5, 'persia': 6};

const INFLUENCE_ROW = {'athens' : 0, 'sparta' : 1, 'argos' : 2, 'corinth' : 3, 'thebes' : 4, 'megara' : 5, 'any' : 6};
const INFLUENCE_COL = {'influence' : 0, 'candidate' : 1, 'assassin' : 2};

const INFLUENCE_PILE = "influence_slot_0";

const SPECIAL_TILES = ['perikles', 'persianfleet', 'slaverevolt', 'brasidas', 'thessalanianallies', 'alkibiades', 'phormio', 'plague'];

const HOPLITE = "hoplite";
const TRIREME = "trireme";

const MIL_DIM = {
    "l": 100,
    "s": 62
}

const PLAYER_COLORS = {
    "E53738" : "red",
    "37BC4C" : "green",
    "39364F" : "black",
    "E5A137" : "orange",
    "ffffff" : "white"
}

// row,column
const LOCATIONS = {
    "amphipolis" : [1,1],
    "lesbos" : [1,2],
    "plataea" :  [1,3],
    "naupactus" : [1,4],
    "potidea" : [1,5],
    "acarnania" : [1,6],
    "attica" : [1,7],
    "melos" : [2,1],
    "epidaurus" : [2,2],
    "pylos" : [2,3],
    "sicily" : [2,4],
    "cephallenia" : [2,5],
    "cythera" : [2,6],
    "spartolus" : [2,7],
    "megara" : [3,1],
    "mantinea" : [3,2],
    "delium" : [3,3],
    "aetolia" : [3,4],
    "corcyra" : [3,5],
    "leucas" : [3,6],
    "solygeia" : [3,7],
}

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.perikles", ebg.core.gamegui, {
        constructor: function(){
            console.log('perikles constructor');
            this.influence_h = 199;
            this.influence_w = 128;
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
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            this.setupSpecialTiles(gamedatas.players, gamedatas.specialtiles);
            this.setupInfluenceTiles(gamedatas.influencetiles, parseInt(gamedatas.decksize));
            this.setupInfluenceCubes(gamedatas.influencecubes);
            this.setupLocationTiles(gamedatas.locationtiles);
            this.setupCandidates(gamedatas.candidates);
            this.setupLeaders(gamedatas.leaders);
            this.setupStatues(gamedatas.statues);
            this.setupMilitary(gamedatas.military);
            this.setupDefeats(gamedatas.defeats);

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        /**
         * Set up special tiles
         * @param {Array} players 
         * @param {Array} specialtiles 
         */
        setupSpecialTiles: function(players, specialtiles) {
            const special_scale = 0.2;
            for (const player_id in players) {
                const player_board_div = document.getElementById('player_board_'+player_id);
                const spec = parseInt(specialtiles[player_id]);

                if (spec == 0) {
                    var specialtile = this.format_block('jstpl_special_back', {id: player_id, scale: special_scale});
                } else {
                    const spec_i = SPECIAL_TILES[Math.abs(spec-1)];
                    specialtile = this.format_block('jstpl_special_tile', {special: spec_i, scale: special_scale});
                    const used = (spec < 0 || player_id != this.player_id);
                    if (used) {
                        tile.classList.add("per_special_tile_used");
                    }
                }
                const tile = dojo.place(specialtile, player_board_div);
                if (spec == 0) {
                    let ttext = _("${player_name}'s Special tile (not used)");
                    ttext = ttext.replace('${player_name}', players[player_id].name);
                    this.addTooltip(tile.id, ttext, '');
                } else {
                    const thtml = this.createSpecialTileTooltip(players[player_id], SPECIAL_TILES[Math.abs(spec-1)]);
                    this.addTooltipHtml(tile.id, thtml, '');
                }
            }
        },

        /**
         * HTML for Special tile tooltip.
         * @param {Object} player 
         * @param {string} tilenum 
         * @returns 
         */
        createSpecialTileTooltip: function(player, special) {
            
            const TITLES = {
                'perikles': _("Perikles"),
                'persianfleet': _("Persian Fleet"),
                'slaverevolt': _("Slave Revolt"),
                'brasidas': _("Brasidas"),
                'thessalanianallies': _("Thessalanian Allies"),
                'alkibiades': _("Alkibiades"),
                'phormio': _("Phormio"),
                'plague': _("Plague")
            };
            const DESC = {
                'perikles': _("Place two Influence cubes in Athens. This tile can be played when it is your turn to select an Influence tile, either just before or just after taking the tile."),
                'persianfleet': _("This tile can be played just before a trireme battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                'slaverevolt': _("This tile can be played when it is your turn to commit forces to a location. Take one Spartan hoplite counter, either from the board or from the controlling player, and place it back in Sparta. That counter cannot be involved in combat this turn. You cannot examine the counter you remove. The counter will come back into play in the next turn."),
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
         * Put influence tiles on board, create deck
         * @param {Array} influence
         * @param {int} decksize
         */
        setupInfluenceTiles: function(influence, decksize) {
            // tiles in slots
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
            for (const tile of influence) {
                const id = tile['id'];
                const city = tile['city'];
                const s = tile['slot'];
                const slot = document.getElementById("influence_slot_"+s);
                const xoff = -1 * INFLUENCE_COL[tile['type']] * INFLUENCE_SCALE * this.influence_w;
                const yoff = -1 * INFLUENCE_ROW[city] * INFLUENCE_SCALE * this.influence_h;
                const card_div = this.format_block('jstpl_influence_tile', {city: city, id: id, x: xoff, y: yoff});
                const card = dojo.place(card_div, slot);
                let ttext = "";
                if (city == "any") {
                    ttext = helptext["any"];
                } else {
                    ttext = helptext[tile['type']];
                }
                const cityname = this.getCityNameTr(city);
                ttext = ttext.replace('${city}', cityname);
                const tooltip = this.format_block('jstpl_influence_tt', {city: cityname, label: helplbl[tile['type']], text: ttext, x: xoff, y: yoff});
                this.addTooltipHtml(card.id, tooltip, '');
                this.decorateInfluenceCard(card.id);
            }
            // deck
            const pile = document.getElementById(INFLUENCE_PILE);
            for (let c = 1; c <= decksize; c++) {
                const cardback = this.format_block('jstpl_influence_back', {id: c, x: -1 * INFLUENCE_SCALE * this.influence_w, y: -6 * INFLUENCE_SCALE * this.influence_h, m: c-1});
                dojo.place(cardback, pile);
            }
            var pile_tt = _("Influence Deck: ${num} cards remaining");
            pile_tt = pile_tt.replace('${num}', decksize);
            this.addTooltip(INFLUENCE_PILE, pile_tt, '');
        },

        /**
         * Return translatable city name text
         * @param {string} city 
         */
        getCityNameTr: function(city) {
            const citynames = {
                "argos" : _("Argos"),
                "athens": _("Athens"),
                "corinth": _("Corinth"),
                "megara": _("Megara"),
                "sparta": _("Sparta"),
                "thebes": _("Thebes"),
                "any": _("Any City")
            };
            return citynames[city];
        },

        /**
         * For Influence cards on display, add Event listeners.
         * @param {string} id 
         */
         decorateInfluenceCard: function(id) {
            const card = document.getElementById(id);
            card.addEventListener('click', () => {
                this.onInfluenceCardSelected(id);
            });
            card.addEventListener('mouseenter', () => {
                this.onInfluenceCardHover(id, true);
            });
            card.addEventListener('mouseout', () => {
                this.onInfluenceCardHover(id, false);
            });
        },

        /**
         * When Influence card is taken.
         * @param {string} id 
         */
        onInfluenceCardSelected: function(id) {
            if (this.checkAction("takeInfluenceTile", true)) {
                console.log("picked card "+id);
            }
       },

        onInfluenceCardHover: function(id, hover) {
            if (this.checkAction("takeInfluenceTile", true)) {
                const card = document.getElementById(id);
                card.style['transform'] = hover ? 'scale(1.1)' : '';
                card.style['transition'] = 'transform 0.5s';
                card.style['box-shadow'] = hover ? 'rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px' : '';
            }
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
                        const cube = this.createInfluenceCube(player_id);
                        const column = document.getElementById(city+"_cubes_"+player_id);
                        dojo.place(cube, column);
                    }
                }

            }
        },

        /**
         * Put all the Location tiles in their slots.
         * @param {Array} locationtiles 
         */
        setupLocationTiles: function(locationtiles) {
            const locw = 124;
            const loch = 195;
            const scale = 0.55;
        
            for (const loc of locationtiles) {
                const slot = loc['slot'];
                const battle = loc['location'];
                const loc_slot = document.getElementById("location_"+slot);
                x = -1 * (LOCATIONS[battle][1]-1) * locw * scale;
                y = -1 * (LOCATIONS[battle][0]-1) * loch * scale;
                const loc_tile = this.format_block('jstpl_location_tile', {id: battle, x: x, y: y});
                dojo.place(loc_tile, loc_slot);
            }
        },

        /**
         * Put cubes in candidate spaces.
         * @param {Object} candidates 
         */
        setupCandidates: function(candidates) {
            for (const [cand, player_id] of Object.entries(candidates)) {
                const candidate_space = document.getElementById(cand);
                const cube = this.createInfluenceCube(player_id);
                dojo.place(cube, candidate_space);
            }
        },

        /**
         * Place Leader tokens on cities.
         * @param {Object} leaders 
         */
        setupLeaders: function(leaders) {
            for (const [city, player_id] of Object.entries(leaders)) {
                const leader = this.createLeaderCounter(player_id, city, "leader", 1);
                const leader_slot = document.getElementById(city+"_leader");
                dojo.place(leader, leader_slot);
            }
        },

        /**
         * Place all statues in city statue areas.
         * @param {Object} statues 
         */
        setupStatues: function(statues) {
            for (let city of CITIES) {
                const citystatues = statues[city];
                if (citystatues) {
                    const statue_area = document.getElementById(city+"_statues");
                    let s = 0;
                    for (const [player_id, num] of Object.entries(citystatues)) {
                        for (let i = 1; i <= parseInt(num); i++) {
                            const statue_div = this.createLeaderCounter(player_id, city, "statue", s+1);
                            const statue = dojo.place(statue_div, statue_area);
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
            const player = this.gamedatas.players[player_id];
            const color = player.color;
            const counter = this.format_block('jstpl_leader', {city: city, type: type, num: n, color: PLAYER_COLORS[color]});
            return counter;
        },

        /**
         * Create a cube div in player's color.
         * @param {int} player_id 
         * @returns colored influence cube
         */
        createInfluenceCube: function(player_id) {
            const player = this.gamedatas.players[player_id];
            const color = player.color;
            const cube = this.format_block('jstpl_cube', {color: color});
            return cube;
        },

        /**
         * For military pools only, not conflict zone
         * @param {Object} military 
         */
        setupMilitary: function(military) {
            const dim_l = 100;
            const dim_s = 62;
            for(const i in military) {
                const counter = military[i];
                const city = counter['city'];
                const unit = counter['type'];
                const strength = counter['strength'];
                const location = counter['location'];
                var xdim, ydim;
                if (unit == HOPLITE) {
                    xdim = dim_s;
                    ydim = dim_l;
                } else if (unit == TRIREME) {
                    xdim = dim_l;
                    ydim = dim_s;
                } else {
                    throw Error("invalid unit type: "+ unit);
                }
                let xoff = -1 * strength * xdim;
                let yoff = -1 * MILITARY_ROW[city] * ydim;
                if (location == city) {
                    const city_military = document.getElementById(city+"_military");
                    const ct = city_military.childElementCount;
                    const top = (unit == TRIREME) ? ydim/2 : 0;
                    const tile_div = this.format_block('jstpl_military', {city: city, type: unit, s: strength, id: counter['id'], x: xoff, y: yoff, m: 2*ct, t: top}); 
                    dojo.place(tile_div, city_military);
                    this.decorateMilitary(city_military);
                }
            }
        },

        /**
         * Make military display available counters
         */
        decorateMilitary: function(city_mil) {
            city_mil.addEventListener('click', () => {
                this.spreadMilitaryUnits(city_mil);
            });
            city_mil.addEventListener('mouseenter', () => {
                this.spreadMilitaryUnits(city_mil);
            });
            city_mil.addEventListener('mouseleave', () => {
                for (const mil of city_mil.children) {
                    mil.style.transform = "";
                }
            });
        },

        /**
         * Spread out all Hoplite and Trireme counters
         */
        spreadMilitaryUnits: function(city_mil) {
            const hoplites = [];
            const triremes = [];
            for (const mil of city_mil.children) {
                if (mil.classList.contains("per_hoplite")) {
                    hoplites.push(mil.id);
                } else if  (mil.classList.contains("per_trireme")) {
                    triremes.push(mil.id);
                }
            }
            hoplites.sort();
            triremes.sort();
            let n = 0;
            let athens_off = 0;
            if (city_mil.id == "athens_military") {
                athens_off = -1 * Math.max((hoplites.length * MIL_DIM.s), (triremes.length * MIL_DIM.l));
            }
            for(h of hoplites) {
                const hop = document.getElementById(h);
                let xoff = athens_off+(n*MIL_DIM.s);
                let yoff = n*-2;
                hop.style.transform = "translate("+xoff+"px,"+yoff+"px)";
                n++;
            }
            n = 0;
            for(t of triremes) {
                const tri = document.getElementById(t);
                let xoff = (-2 * hoplites.length) + athens_off+(n*MIL_DIM.l);
                let yoff = MIL_DIM.s+(n*-2);
                tri.style.transform = "translate("+xoff+"px,"+yoff+"px)";
                n++;
            }
        },

        /**
         * Place Defeat counters on cities.
         * @param {Object} defeats 
         */
        setupDefeats: function(defeats) {
            for (const [city, num] of Object.entries(defeats)) {
                for (let d = 1; d <= num; d++) {
                    const def_ctr = this.format_block('jstpl_defeat', {city: city, num: d} );
                    const def_div = document.getElementById(city+'_defeat_slot_'+d);
                    dojo.place(def_ctr, def_div);
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
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
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/perikles/perikles/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your perikles.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
