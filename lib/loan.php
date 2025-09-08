<?php

namespace AdvancedLoanPhp;

class advancedLoan {
    private $insuranceRate; # taux de l'assurance annuel
    private $insuranceBase = 'remainingCapital'; # remainingCapital | initialCapital
    private $creditRate; # taux du crédit annuel
    private $monthlyPayment; # mensualité
    private $amountBorrowed; # montant emprunté
    private $loanTerm; # Durée du crédit en mois
    private $margin = 0.001;
    private $marginDelta = Null;
    private $pass = 0;
    private $passMax = 100;
    private $failed = False;
    private $modificators = [];
    private $monthlyPaymentModification = 0;
    private $monthlyPaymentReplace = False;

    private $headers = [
        "remainingCapitalBefore" => "Capital restant dû Avant",
        "monthlyPayment" => "Mensualité payée",
        "amortization" => "Capital remboursé",
        "interest" => "Montant d'intérêts",
        "amountInsurance" => "Montant d'assurance",
        "remainingCapital" => "Capital restant dû après",
    ];

    private $cache = [];

    public function setInsuranceRate($insuranceRate) {
        $this->insuranceRate = $insuranceRate;
    }

    public function setInsuranceBase($insuranceBase) {
        if (in_array($insuranceBase, ['remainingCapital', 'initialCapital']))
            $this->insuranceBase = $insuranceBase;
        else
            throw new \Exception('insuranceBase have to be remainingCapital or initialCapital');
    }

    public function setCreditRate($creditRate) {
        $this->creditRate = $creditRate;
    }

    public function setLoanTerm($loanTerm) {
        $this->loanTerm = $loanTerm;
    }

    public function setAmountBorrowed($amountBorrowed) {
        $this->amountBorrowed = $amountBorrowed;
    }

    public function setModificator($k, $amount = Null, $term = Null, $amountReplace = False) {
        $this->modificators[] = ['k' => $k, 'amount' => $amount, 'term' => $term, 'amountReplace' => $amountReplace];
    }

    public function amountInsurance($k) {
        switch ($this->insuranceBase) {
            case 'remainingCapital':
                $this->cache[$k]['amountInsurance'] = $this->remainingCapital($k - 1) * ($this->insuranceRate / 100) / 12;
                break;
            case 'initialCapital':
                $this->cache[$k]['amountInsurance'] = $this->amountBorrowed * ($this->insuranceRate / 100) / 12;
                break;
        }
        return $this->cache[$k]['amountInsurance'];
    }

    public function interest($k) {
        $this->cache[$k]['interest'] = $this->remainingCapital($k - 1) * ($this->creditRate / 100) / 12;
        return $this->cache[$k]['interest'];
    }

    public function amortization($k) {
#        print "$k => ".$this->cache[$k]['monthlyPayment']."<br>";
        $this->cache[$k]['amortization'] = $this->cache[$k]['monthlyPayment'] - $this->amountInsurance($k) - $this->interest($k);
        return $this->cache[$k]['amortization'];
    }

    public function remainingCapital($k) {
        if ($k > $this->loanTerm)
            return Null;
        if (array_key_exists($k, $this->cache) && array_key_exists('remainingCapital', $this->cache[$k]))
            return $this->cache[$k]['remainingCapital'];
        if ($this->monthlyPaymentReplace)
            $this->cache[$k]['monthlyPayment'] = $this->monthlyPaymentModification;
        else
            $this->cache[$k]['monthlyPayment'] = $this->monthlyPayment + $this->monthlyPaymentModification;
        if (!array_key_exists($k, $this->cache) || !array_key_exists('remainingCapital', $this->cache[$k]))
            $this->cache[$k]['remainingCapital'] = $this->remainingCapital($k - 1) - $this->amortization($k);
        if (array_key_exists($k - 1 , $this->cache))
            $this->cache[$k]['remainingCapitalBefore'] = $this->cache[$k - 1]['remainingCapital'];
        return $this->cache[$k]['remainingCapital'];
    }

    public function findMonthlyPayment($ks = 0) {
        $this->findMonthlyPaymentCalculate($ks);
        if (!$this->failed)
            foreach($this->modificators as $values) {
                $this->pass = 0;
                $this->findMonthlyPaymentCalculate($values['k'], $values);
            }
    }

    public function findMonthlyPaymentCalculate($ks = 0, $modificator = Null) {
        $this->pass ++;
#        print "Pass $ks $this->pass<br/>";
        if ($this->pass == 1 && $modificator) {
            if ($modificator['term'])
                $this->loanTerm += $modificator['term'];
        }
        if ($this->pass == 1) {
            $this->monthlyPayment = $this->amountBorrowed / $this->loanTerm * 2;
#            print "Init monthlyPayment : $this->monthlyPayment<br/>";
            $this->failed = False;
            $this->marginDelta = Null;
        }
#        print "$this->pass $ks $this->monthlyPayment <br/>";
        if ($ks === 0) {
            $this->cache = [];
            $this->cache[0]['remainingCapital'] = $this->amountBorrowed;
        } else {
            for($k = $ks; $k <= max(array_keys($this->cache)); $k++) {
                unset($this->cache[$k]);
            }
        }
        for($k = $ks; $k <= $this->loanTerm ; $k++) {
            if (is_array($modificator) && $k == $ks && $modificator['k'] == $k && $modificator['amount'] !== Null) {
                $this->monthlyPaymentModification = $modificator['amount'];
                $this->monthlyPaymentReplace = $modificator['amountReplace'];
            }
            $this->remainingCapital($k);
            $this->monthlyPaymentModification = 0;
            $this->monthlyPaymentReplace = False;
        }
        ksort($this->cache);
        $lastRemainingCapital = $this->cache[$this->loanTerm]['remainingCapital'];
        if (abs($lastRemainingCapital) > $this->margin && $this->pass < $this->passMax) {
            if ($lastRemainingCapital < 0)
                $factor = -1;
            else
                $factor = 1;
            if ($this->marginDelta === Null)
                $this->marginDelta = $this->monthlyPayment;
            else
                $this->marginDelta = $this->marginDelta / 2;
            $this->monthlyPayment = $this->monthlyPayment + $factor * $this->marginDelta;
            $this->findMonthlyPaymentCalculate($ks, $modificator);
#            print "End Pass ".$this->pass."<br>";
        }
        if ($this->pass >= $this->passMax)
            $this->failed = True;
    }

    public function getHtml($amortizationSchedule = True) {
        unset($this->cache[0]);
        $html = "
<style>
table {
    width: 100%;
}
td, th {
    text-align: center;
}
</style>
";
        $totals = [
            "monthlyPayment" => 0,
            "amortization" => 0,
            "interest" => 0,
            "amountInsurance" => 0,
        ];

        foreach($this->cache as $k => $values) {
            foreach($totals as $key => $value) {
                $totals[$key] += $this->cache[$k][$key];
            }
        }

        $html .= "<b>Données Initiales</b><br/>\n";
        if (! $this->modificators)
            $html .= "Mensualité : ".number_format($this->monthlyPayment, 2, ',', ' ')." €<br/>\n";
        $html .= "Taux de l'assurance annuel : ".number_format($this->insuranceRate, 2, ',', ' ')." %<br/>\n";
        $html .= "Taux du crédit annuel : ".number_format($this->creditRate, 2, ',', ' ')." %<br/>\n";
        $html .= "Montant emprunté : ".number_format($this->amountBorrowed, 2, ',', ' ')." €<br/>\n";
        $html .= "Durée : ".max(array_keys($this->cache))." Mois<br/>\n";
        $html .= "Succès : ".(($this->failed)?'Non':"Oui")."<br>\n"; 
        $html .= "<br/>";

        if ($this->modificators) {
            $html .= "<b>Modifications</b><br/>\n";
            foreach($this->modificators as $modificator) {
                if ($modificator['amountReplace'])
                    $html .= sprintf ("Echeance %s : %s € (remplace l'échéance) / + %s Mois<br/>\n", $modificator['k'], $modificator['amount'], $modificator['term']);
                else
                    $html .= sprintf ("Echeance %s : remboursement %s € / + %s Mois<br/>\n", $modificator['k'], $modificator['amount'], $modificator['term']);
            }
            $html .= "<br/>\n";
        }

        $html .= "<b>Récapitulatif</b><br/>\n";
        foreach($totals as $key => $value) {
            $html .= sprintf("%s : %s €<br/>\n", $this->headers[$key], number_format($totals[$key], 2, ',', ' '));
        }
        $html .= "Coût du crédit : ".number_format($totals['interest'] + $totals['amountInsurance'], 2, ',', ' ')." €\n";
        if ($amortizationSchedule) {
            $html .= "<br/>\n";
            $html .= "<br/>\n";
            $html .= "<table>\n";
            $html .= "<tr>\n";
            $html .= "  <th>#</th>\n";
            foreach($this->headers as $key => $name)
                $html .= "  <th>".$name."</th>\n";
            $html .= "</tr>\n";
            foreach($this->cache as $k => $values) {
                $html .= "<tr>\n";
                $html .= "<td>$k</td>\n";
                foreach($this->headers as $key => $name)
                    if (array_key_exists($key, $values))
                        $html .= "<td>".number_format($values[$key], 2, ',', ' ')." €</td>\n";
                    else
                        $html .= "<td></td>\n";
    
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";
            return $html;    
        }
    }
}