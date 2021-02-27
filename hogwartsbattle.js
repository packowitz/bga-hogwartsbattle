/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * HogwartsBattle implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * hogwartsbattle.js
 *
 * HogwartsBattle user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
      "dojo", "dojo/_base/declare",
      "ebg/core/gamegui",
      "ebg/counter",
      "ebg/stock",
      "ebg/zone"
  ],
function (dojo, declare) {
    return declare("bgagame.hogwartsbattle", ebg.core.gamegui, {
        constructor: function(){
            console.log('hogwartsbattle constructor');
            this.cardwidth = 200;
            this.cardheight = 280;
            this.cardsPerRow = 16;

            this.health_counters = {};
            this.attack_counters = {};
            this.influence_counters = {};
            this.handCards_counters = {};

            this.discard_piles = {};

            // this.handCardsZone = new ebg.zone();
            // this.handCardsZone.create(this, 'myhand', 100, 140);
            // this.handCardsZone.setPattern('horizontalfit');
            //
            // this.discardZone = new ebg.zone();
            // this.discardZone.create(this, 'played_cards', 100, 140);
            // this.discardZone.setPattern('verticalfit');
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
            
            // Setting up player boards
            for(var player_id in gamedatas.players)
            {
                var player = gamedatas.players[player_id];
                         
                var player_board_div = $('player_board_' + player_id);
                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div );

                // create counter per player
                this.health_counters[player_id] = new ebg.counter();
                this.health_counters[player_id].create('health_stat_p' + player_id);
                this.health_counters[player_id].setValue(player.health);

                this.attack_counters[player_id] = new ebg.counter();
                this.attack_counters[player_id].create('attack_stat_p' + player_id);
                this.attack_counters[player_id].setValue(player.attack);

                this.influence_counters[player_id] = new ebg.counter();
                this.influence_counters[player_id].create('influence_stat_p' + player_id);
                this.influence_counters[player_id].setValue(player.influence);

                this.handCards_counters[player_id] = new ebg.counter();
                this.handCards_counters[player_id].create('hand_cards_stat_p' + player_id);
                this.handCards_counters[player_id].setValue(player.hand_cards);

                // discard pile
                this.discard_piles[player_id] = new ebg.zone();
                this.discard_piles[player_id].create(this, 'player_discard_' + player_id, 100, 140);
                this.discard_piles[player_id].setPattern('horizontalfit');
                this.discard_piles[player_id].item_margin = 4;

                for (let card_discarded_id in player.discard_cards) {
                    let card_discarded = player.discard_cards[card_discarded_id];
                    console.log('discarded card:');
                    console.log(card_discarded);

                    let elementId = 'discard_p' + player_id + '_' + card_discarded.id
                    dojo.place(
                      this.format_block( 'jstpl_howarts_card', {
                          element_id: elementId,
                          posX: -100 * parseInt(card_discarded.type_arg),
                          posY: 100 * parseInt(card_discarded.type),
                      }), 'player_discard_' + player_id );
                    this.discard_piles[player_id].placeInZone(elementId);
                }
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"

            var customStyle = document.createElement('style');
            customStyle.type = 'text/css';
            customStyle.innerHTML = `.card_size_50p { background-size: ${this.cardsPerRow * this.cardwidth * 0.5}px; }`;
            document.getElementsByTagName('head')[0].appendChild(customStyle);

            // Hogwarts cards
            this.hogwartsCards = new ebg.stock();
            this.hogwartsCards.create(this, $('hogwarts_cards'), this.cardwidth * 0.5, this.cardheight * 0.5);
            this.hogwartsCards.image_items_per_row = this.cardsPerRow;
            this.hogwartsCards.extraClasses='card_size_50p';

            // Played cards
            this.playedCards = new ebg.stock();
            this.playedCards.create(this, $('played_cards'), this.cardwidth * 0.5, this.cardheight * 0.5);
            this.playedCards.image_items_per_row = this.cardsPerRow;
            this.playedCards.setSelectionMode(0);
            this.playedCards.extraClasses='card_size_50p';

            // Player hand
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('myhand'), this.cardwidth * 0.5, this.cardheight * 0.5);
            this.playerHand.image_items_per_row = this.cardsPerRow;
            this.playerHand.extraClasses='card_size_50p';

            // Create cards types:
            for (var gameNr = 0; gameNr <= 1; gameNr++) {
                for (var cardNr = 0; cardNr < this.cardsPerRow; cardNr++) {
                    // Build card type id
                    var card_type_id = this.getHogwartsCardTypeId(gameNr, cardNr);
                    this.playerHand.addItemType(card_type_id, 0, g_gamethemeurl + 'img/hogwarts_cards.png', card_type_id);
                    this.hogwartsCards.addItemType(card_type_id, 0, g_gamethemeurl + 'img/hogwarts_cards.png', card_type_id);
                    this.playedCards.addItemType(card_type_id, 0, g_gamethemeurl + 'img/hogwarts_cards.png', card_type_id);
                }
            }

            for (let i in gamedatas.played_cards) {
                let card = gamedatas.played_cards[i];
                this.playedCards.addToStockWithId(this.getHogwartsCardTypeId(card.type, card.type_arg), card.id);
            }

            this.revealHogwartsCards(gamedatas.hogwarts_cards);
            this.drawHogwartsCards(gamedatas.hand);

            dojo.connect( this.hogwartsCards, 'onChangeSelection', this, 'onHogwartsCardSelectionChanged' );
            dojo.connect( this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
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
                    case 'playerTurn':
                        this.addActionButton( 'endTurnId', 'End turn', 'onEndTurn' );
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

        updatePlayerStats(players) {
            if (players) {
                for(let playerId in players) {
                    let player = players[playerId];

                    let healthDiff = player.health - this.health_counters[playerId].getValue();
                    this.health_counters[playerId].incValue(healthDiff);

                    let attackDiff = player.attack - this.attack_counters[playerId].getValue();
                    this.attack_counters[playerId].incValue(attackDiff);

                    let influenceDiff = player.influence - this.influence_counters[playerId].getValue();
                    this.influence_counters[playerId].incValue(influenceDiff);

                    let handCardsDiff = player.hand_cards - this.handCards_counters[playerId].getValue();
                    this.handCards_counters[playerId].incValue(handCardsDiff);
                }
            }
        },

        getHogwartsCardTypeId : function(gameNr, cardNr) {
            return parseInt(gameNr) * this.cardsPerRow + parseInt(cardNr);
        },

        revealHogwartsCards: function(hogwartsCards) {
            if (hogwartsCards) {
                for (let i in hogwartsCards) {
                    let card = hogwartsCards[i];
                    this.hogwartsCards.addToStockWithId(this.getHogwartsCardTypeId(card.type, card.type_arg), card.id);
                }
            }
        },

        drawHogwartsCards: function(hogwartsCards) {
            if (hogwartsCards) {
                for ( var i in hogwartsCards) {
                    var card = hogwartsCards[i];
                    this.playerHand.addToStockWithId(this.getHogwartsCardTypeId(card.type, card.type_arg), card.id);
                }
            }
        },


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        onEndTurn: function (evt) {
            dojo.stopEvent(evt);

            let action = 'endTurn';
            if (this.checkAction(action, true)) {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onHogwartsCardSelectionChanged : function() {
            var items = this.hogwartsCards.getSelectedItems();
            if (items.length > 0) {
                let card = items[0];
                let action = 'acquireHogwartsCard';
                if (this.checkAction(action, true)) {
                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                        id : card.id,
                        lock : true
                    }, this, function(result) {}, function(is_error) {});
                }
                this.hogwartsCards.unselectAll();

                // this.playerHand.addToStockWithId(card.type, card.id, 'hogwarts_cards_item_' + card.id);
                // this.hogwartsCards.removeFromStockById(card.id);
                //
                // this.hogwartsCards.unselectAll();
            }
        },

        onPlayerHandSelectionChanged : function() {
            var items = this.playerHand.getSelectedItems();
            if (items.length > 0) {
                let card = items[0];
                let action = 'playCard';
                if (this.checkAction(action, true)) {
                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                        id : card.id,
                        lock : true
                    }, this, function(result) {}, function(is_error) {});
                }
                // this.playedCards.addToStockWithId(card.type, card.id, 'myhand_item_' + card.id);
                // this.playerHand.removeFromStockById(card.id);

                this.playerHand.unselectAll();
            }
        },
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/hogwartsbattle/hogwartsbattle/myAction.html", { 
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
                  your hogwartsbattle.game.php file.
        
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
            dojo.subscribe( 'endTurn', this, "notif_endTurn" );
            dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            dojo.subscribe( 'acquireHogwartsCard', this, "notif_acquireHogwartsCard" );
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

        notif_endTurn: function(notif) {
            this.updatePlayerStats(notif.args.players);
            this.revealHogwartsCards(notif.args.new_hogwarts_cards);
            this.playedCards.removeAll();
            if (this.player_id == notif.args.player_id) {
                this.playerHand.removeAll();
                this.drawHogwartsCards(notif.args.new_hand_cards);
            }
        },

        notif_cardPlayed: function(notif) {
            this.updatePlayerStats(notif.args.players);
            let typeId = this.getHogwartsCardTypeId(notif.args.card_game_nr, notif.args.card_card_nr);
            if (this.player_id == notif.args.player_id) {
                this.playedCards.addToStockWithId(typeId, notif.args.card_id, 'myhand_item_' + notif.args.card_id);
                this.playerHand.removeFromStockById(notif.args.card_id);
            }
        },

        notif_acquireHogwartsCard: function(notif) {
            this.updatePlayerStats(notif.args.players);
            let typeId = this.getHogwartsCardTypeId(notif.args.card_game_nr, notif.args.card_card_nr);
            this.playerHand.addToStockWithId(typeId, notif.args.new_card_id, 'hogwarts_cards_item_' + notif.args.card_id);
            this.hogwartsCards.removeFromStockById(notif.args.card_id);
            // move acquired card to player
            // notif.args.card_id
            // notif.args.player_id
        },
   });
});
