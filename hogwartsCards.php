<?php

class HogwartsCard {
    public $typeId;
    public $gameNr;
    public $cardNr;
    public $name;
    public $cost;
    public $type;
    public $autoplay;
    public $onPlay;
    public $onPlayEffect;
    public $onDiscard;
    public $onHand;
    public $desc;

    public function __construct($gameNr, $cardNr, $name, $cost, $type, $autoplay, $onPlay, $onPlayEffect, $onDiscard, $onHand, $desc) {
        $this->typeId = HogwartsCards::cardId($gameNr, $cardNr);
        $this->gameNr = $gameNr;
        $this->cardNr = $cardNr;
        $this->name = $name;
        $this->cost = $cost;
        $this->type = $type;
        $this->autoplay = $autoplay;
        $this->onPlay = $onPlay;
        $this->onPlayEffect = $onPlayEffect;
        $this->onDiscard = $onDiscard;
        $this->onHand = $onHand;
        $this->desc = $desc;
    }
}

class HogwartsCards {
    public static $harryId = 1;
    public static $ronId = 2;
    public static $hermioneId = 3;
    public static $nevilleId = 4;
    public static $spellType = 'SPELL';
    public static $itemType = 'ARTIFACT';
    public static $allyType = 'ALLY';

    public $hogwartsCards;

    function __construct() {
        $this->hogwartsCards = array(
            0 => new HogwartsCard(0, 0, clienttranslate('Alohomora'), 0, self::$spellType, true, '+1inf', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'))),

            1 => new HogwartsCard(0, 1, clienttranslate('Firebolt'), 0, self::$itemType, true, '+1att', '+1inf_onDefVil', null, null,
                array('onPlay' => clienttranslate('Gain 1 ${attack_token}'), 'onDefeatVillain' => clienttranslate('If you defeat a Villain, also gain 1 ${influence_token}'))),

            2 => new HogwartsCard(0, 2, clienttranslate('Invisibility Cloak'), 0, self::$itemType, true, '+1inf', null, null, 'max1dmg',
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'), 'onHand' => clienttranslate('If this is in your hand, you can\'t lose more than 1 ${health_icon} from each Dark Arts event or Villain'))),

            3 => new HogwartsCard(0, 3, clienttranslate('Hedwig'), 0, self::$allyType, false, 'c[+1att|+2hp]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 1 ${attack_token} or gain 2 ${health_icon}'))),

            4 => new HogwartsCard(0, 4, clienttranslate('Alohomora'), 0, self::$spellType, true, '+1inf', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'))),

            5 => new HogwartsCard(0, 5, clienttranslate('Cleansweep 11'), 0, self::$itemType, true, '+1att', '+1inf_onDefVil', null, null,
                array('onPlay' => clienttranslate('Gain 1 ${attack_token}'), 'onDefeatVillain' => clienttranslate('If you defeat a Villain, also gain 1 ${influence_token}'))),

            6 => new HogwartsCard(0, 6, clienttranslate('Bertie Botts Every Flavour Beans'), false, 0, self::$itemType, '+1inf_+1att_xAllyPlayed', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}. For each Ally played this turn, gain 1 ${attack_token}'))),

            7 => new HogwartsCard(0, 7, clienttranslate('Pigwidgeon'), 0, self::$allyType, false, 'c[+1att|+2hp]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 1 ${attack_token} or gain 2 ${health_icon}'))),

            8 => new HogwartsCard(0, 8, clienttranslate('Alohomora'), 0, self::$spellType, true, '+1inf', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'))),

            9 => new HogwartsCard(0, 9, clienttranslate('Tales of Beedle the Bard'), 0, self::$itemType, false, 'c[+2inf|+1inf_all]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 2 ${influence_token} or ALL Heroes gain 1 ${influence_token}'))),

            10 => new HogwartsCard(0, 10, clienttranslate('Time Turner'), 0, self::$itemType, true, '+1inf', 'spells_top_deck', null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'), 'onAcquire' => clienttranslate('You may put Spells you acquire on top of your deck instead of in your discard pile'))),

            11 => new HogwartsCard(0, 11, clienttranslate('Crookshanks'), 0, self::$allyType, false, 'c[+1att|+2hp]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 1 ${attack_token} or gain 2 ${health_icon}'))),

            12 => new HogwartsCard(0, 12, clienttranslate('Alohomora'), 0, self::$spellType, true, '+1inf', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'))),

            13 => new HogwartsCard(0, 13, clienttranslate('Remembrall'), 0, self::$itemType, true, '+1inf', null, '+2inf', null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'), 'onDiscard' => clienttranslate('If you discard this, also gain 2 ${influence_token}'))),

            14 => new HogwartsCard(0, 14, clienttranslate('Mandrake'), 0, self::$itemType, false, 'c[+1att|+2hp_any]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 1 ${attack_token} or any one Hero gains 2 ${health_icon}'))),

            15 => new HogwartsCard(0, 15, clienttranslate('Trevor'), 0, self::$allyType, false, 'c[+1att|+2hp]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 1 ${attack_token} or gain 2 ${health_icon}'))),

            100 => new HogwartsCard(1, 0, clienttranslate('Wingardium Leviosa'), 2, self::$spellType, true, '+1inf', 'items_top_deck', null, null,
                array('onPlay' => clienttranslate('Gain 1 ${influence_token}'), 'onAcquire' => clienttranslate('You may put Items you acquire on top of your deck instead of in your discard pile'))),

            101 => new HogwartsCard(1, 1, clienttranslate('Reparo'), 3, self::$spellType, false, 'c[+2inf|+1card]', null, null, null,
                array('onPlay' => clienttranslate('Choose one: Gain 2 ${influence_token} or draw a card'))),

            102 => new HogwartsCard(1, 2, clienttranslate('Incendio'), 4, self::$spellType, false, '+1att_+1card', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${attack_token} and draw a card'))),

            103 => new HogwartsCard(1, 3, clienttranslate('Lumos'), 4, self::$spellType, false, '+1card_all', null, null, null,
                array('onPlay' => clienttranslate('ALL Heroes draw a card'))),

            104 => new HogwartsCard(1, 4, clienttranslate('Descendo'), 5, self::$spellType, true, '+2att', null, null, null,
                array('onPlay' => clienttranslate('Gain 2 ${attack_token}'))),

            105 => new HogwartsCard(1, 5, clienttranslate('Essence of Dittany'), 2, self::$itemType, false, 'c[+2hp_any]', null, null, null,
                array('onPlay' => clienttranslate('Any one Hero gains 2 ${health_icon}'))),

            106 => new HogwartsCard(1, 6, clienttranslate('Quidditch Gear'), 3, self::$itemType, true, '+1att_+1hp', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${attack_token} and 1 ${health_icon}'))),

            107 => new HogwartsCard(1, 7, clienttranslate('Sorting Hat'), 4, self::$itemType, true, '+2inf', 'allies_on_top', null, null,
                array('onPlay' => clienttranslate('Gain 2 ${influence_token}'), 'onAcquire' => clienttranslate('You may put Allies you acquire on top of your deck instead of in your discard pile'))),

            108 => new HogwartsCard(1, 8, clienttranslate('Golden Snitch'), 5, self::$itemType, false, '+2inf_+1card', null, null, null,
                array('onPlay' => clienttranslate('Gain 2 ${influence_token} and draw a card'))),

            109 => new HogwartsCard(1, 9, clienttranslate('Oliver Wood'), 3, self::$allyType, true, '+1att', 'c[+2hp_any_onDefVil]', null, null,
                array('onPlay' => clienttranslate('Gain 1 ${attack_token}'), 'onDefeatVillain' => clienttranslate('If you defeat a Villain, also any one Hero gains 2 ${health_icon}'))),

            110 => new HogwartsCard(1, 10, clienttranslate('Rubeus Hagrid'), 4, self::$allyType, true, '+1att_+1hp_all', null, null, null,
                array('onPlay' => clienttranslate('Gain 1 ${attack_token} and ALL Heroes gain 1 ${health_icon}'))),

            111 => new HogwartsCard(1, 11, clienttranslate('Albus Dumbledor'), 8, self::$allyType, false, '+1att_all_+1inf_all_+1hp_all_+1card_all', null, null, null,
                array('onPlay' => clienttranslate('ALL Heroes gain 1 ${attack_token}, 1 ${influence_token}, 1 ${health_icon} and draw a card'))),
        );
    }

    public function game1Cards() {
        $cards = array();
        $cards[] = $this->asCard($this->hogwartsCards[100], 3);
        $cards[] = $this->asCard($this->hogwartsCards[101], 6);
        $cards[] = $this->asCard($this->hogwartsCards[102], 4);
        $cards[] = $this->asCard($this->hogwartsCards[103], 2);
        $cards[] = $this->asCard($this->hogwartsCards[104], 2);
        $cards[] = $this->asCard($this->hogwartsCards[105], 4);
        $cards[] = $this->asCard($this->hogwartsCards[106], 4);
        $cards[] = $this->asCard($this->hogwartsCards[107]);
        $cards[] = $this->asCard($this->hogwartsCards[108]);
        $cards[] = $this->asCard($this->hogwartsCards[109]);
        $cards[] = $this->asCard($this->hogwartsCards[110]);
        $cards[] = $this->asCard($this->hogwartsCards[111]);
        return $cards;
    }

    public function heroStartingCards($heroId) {
        $cards = array();
        switch ($heroId) {
            case self::$harryId:
                $cards[] = $this->asCard($this->hogwartsCards[0], 7);
                $cards[] = $this->asCard($this->hogwartsCards[1]);
                $cards[] = $this->asCard($this->hogwartsCards[2]);
                $cards[] = $this->asCard($this->hogwartsCards[3]);
                break;
            case self::$ronId:
                $cards[] = $this->asCard($this->hogwartsCards[4], 7);
                $cards[] = $this->asCard($this->hogwartsCards[5]);
                $cards[] = $this->asCard($this->hogwartsCards[6]);
                $cards[] = $this->asCard($this->hogwartsCards[7]);
                break;
            case self::$hermioneId:
                $cards[] = $this->asCard($this->hogwartsCards[8], 7);
                $cards[] = $this->asCard($this->hogwartsCards[9]);
                $cards[] = $this->asCard($this->hogwartsCards[10]);
                $cards[] = $this->asCard($this->hogwartsCards[11]);
                break;
            case self::$nevilleId:
                $cards[] = $this->asCard($this->hogwartsCards[12], 7);
                $cards[] = $this->asCard($this->hogwartsCards[13]);
                $cards[] = $this->asCard($this->hogwartsCards[14]);
                $cards[] = $this->asCard($this->hogwartsCards[15]);
                break;
        }
        return $cards;
    }

    public function getCard($gameNr, $cardNr) {
        return $this->hogwartsCards[self::cardId($gameNr, $cardNr)];
    }

    public static function cardId($gameNr, $cardNr) {
        return (int)$gameNr * 100 + (int)$cardNr;
    }

    public function asCard($card, $x = 1) {
        return array ('type' => $card->gameNr,'type_arg' => $card->cardNr,'nbr' => $x );
    }


}