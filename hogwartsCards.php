<?php

class HogwartsCard {
    public $gameNr;
    public $cardNr;
    public $name;
    public $cost;
    public $type;
    public $onPlay;
    public $onDiscard;
    public $onHand;

    public function __construct($gameNr, $cardNr, $name, $cost, $type, $onPlay = array(), $onDiscard = array(), $onHand = array())
    {
        $this->gameNr = $gameNr;
        $this->cardNr = $cardNr;
        $this->name = $name;
        $this->cost = $cost;
        $this->type = $type;
        $this->onPlay = $onPlay;
        $this->onDiscard = $onDiscard;
        $this->onHand = $onHand;
    }
}

class HogwartsCards {
    public static $harryId = 1;
    public static $ronId = 2;
    public static $hermioneId = 3;
    public static $nevilleId = 4;
    public static $spellType = "SPELL";
    public static $itemType = "ARTIFACT";
    public static $allyType = "ALLY";

    function __construct() {
        $this->hogwartsCards = array(
            0 => new HogwartsCard(0, 0, "Alohomora", 0, self::$spellType),
            1 => new HogwartsCard(0, 1, "Firebolt", 0, self::$itemType),
            2 => new HogwartsCard(0, 2, "Invisibility Cloak", 0, self::$itemType),
            3 => new HogwartsCard(0, 3, "Hedwig", 0, self::$allyType),
            4 => new HogwartsCard(0, 4, "Alohomora", 0, self::$spellType),
            5 => new HogwartsCard(0, 5, "Cleansweep 11", 0, self::$itemType),
            6 => new HogwartsCard(0, 6, "Bertie Botts Every Flavour Beans", 0, self::$itemType),
            7 => new HogwartsCard(0, 7, "Pigwidgeon", 0, self::$allyType),
            8 => new HogwartsCard(0, 8, "Alohomora", 0, self::$spellType),
            9 => new HogwartsCard(0, 9, "Tales of Beedle the Bard", 0, self::$itemType),
            10 => new HogwartsCard(0, 10, "Time Turner", 0, self::$itemType),
            11 => new HogwartsCard(0, 11, "Crookshanks", 0, self::$allyType),
            12 => new HogwartsCard(0, 12, "Alohomora", 0, self::$spellType),
            13 => new HogwartsCard(0, 13, "Remembrall", 0, self::$itemType),
            14 => new HogwartsCard(0, 14, "Mandrake", 0, self::$itemType),
            15 => new HogwartsCard(0, 15, "Trevor", 0, self::$allyType),
            100 => new HogwartsCard(1, 0, "Wingardium Leviosa", 2, self::$spellType),
            101 => new HogwartsCard(1, 1, "Reparo", 3, self::$spellType),
            102 => new HogwartsCard(1, 2, "Incendio", 4, self::$spellType),
            103 => new HogwartsCard(1, 3, "Lumos", 4, self::$spellType),
            104 => new HogwartsCard(1, 4, "Descendo", 5, self::$spellType),
            105 => new HogwartsCard(1, 5, "Essence of Dittany", 2, self::$itemType),
            106 => new HogwartsCard(1, 6, "Quidditch Gear", 3, self::$itemType),
            107 => new HogwartsCard(1, 7, "Sorting Hat", 4, self::$itemType),
            108 => new HogwartsCard(1, 8, "Golden Snitch", 5, self::$itemType),
            109 => new HogwartsCard(1, 9, "Oliver Wood", 3, self::$allyType),
            110 => new HogwartsCard(1, 10, "Rubeus Hagrid", 4, self::$allyType),
            111 => new HogwartsCard(1, 11, "Albus Dumbledor", 8, self::$allyType),
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

    private function asCard($card, $x = 1) {
        return array ('type' => $card->gameNr,'type_arg' => $card->cardNr,'nbr' => $x );
    }


}