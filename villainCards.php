<?php

class VillainCard {
    public $id;
    public $gameNr;
    public $cardNr;
    public $name;
    public $health;
    public $effect;
    public $ability;
    public $reward;
    public $desc;

    public function __construct($gameNr, $cardNr, $name, $health, $effect, $ability, $reward, $desc) {
        $this->id = VillainCards::cardId($gameNr, $cardNr);
        $this->gameNr = $gameNr;
        $this->cardNr = $cardNr;
        $this->name = $name;
        $this->health = $health;
        $this->effect = $effect;
        $this->ability = $ability;
        $this->reward = $reward;
        $this->desc = $desc;
    }
}

class VillainCards {

    public $villainCards;

    function __construct() {
        $this->villainCards = array(
            0 => new VillainCard(0, 0, clienttranslate('Draco Malfoy'), 6, '2dmg_onLocationToken', null, '-1loc_token',
                clienttranslate('Each time ${location_token} is added to the Location, active Hero loses 2 ${health_icon}')),

            1 => new VillainCard(0, 1, clienttranslate('Crabbe & Goyle'), 5, '1dmg_onDiscard', null, '+1card_all',
                clienttranslate('Each time a Dark Arts event or Villain causes a Hero to discard a card, that Hero loses 1 ${health_icon}')),

            2 => new VillainCard(0, 2, clienttranslate('Quirinus Quirrell'), 6, null, '1dmg', '+1inf_+1hp_all',
                clienttranslate('Active Hero loses 1 ${health_icon}'))
        );
    }

    public function gameCards($gameNr) {
        $cards = array();
        if ($gameNr >= 1) {
            $cards[] = $this->asCard($this->villainCards[0]);
            $cards[] = $this->asCard($this->villainCards[1]);
            $cards[] = $this->asCard($this->villainCards[2]);
        }
        return $cards;
    }

    public function getVillainCard($gameNr, $cardNr) {
        return $this->villainCards[self::cardId($gameNr, $cardNr)];
    }

    public static function cardId($gameNr, $cardNr) {
        return (int)$gameNr * 100 + (int)$cardNr;
    }

    public function asCard($card, $x = 1) {
        return array ('type' => $card->gameNr,'type_arg' => $card->cardNr,'nbr' => $x );
    }


}