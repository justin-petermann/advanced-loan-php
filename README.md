# Contexte

Ce projet n'a pas pour but pour le moment d'être sexy.
Il se veut être une lib simple d'utilisation qui calcul un crédit et sort les données principales et le tableau d'ammortissement pour essayer de simuler au plus juste les mensualités sur un crédit dont le taux d'assurance est calculé sur le taux de capital restant dû (ou pas)

 - Définition du taux annuel d'assurance
 - Définition du taux annuel du crédit
 - Définition de la durée du crédit initial
 - Définition du montant emprunté
 - Définition si le montant de l'assurance mensuel est sur le capital dû ou initial
 - Calcul de la mensualité et du tableau d'ammortissement
 - Affichage du tableau d'ammortissement
 - Gestion de modificateurs en définissant l'échéance à laquel elle est ajoutée pour injecter un montant dans le crédit (en replacement de la mensualité en ou en ajout), ajout ou retrait du nombre de mois de durée du crédit. (dans le cadre d'un crédit relais par exemple)

> Si vous trouvez une erreur de calcul, merci de m'en faire part.

> Ceci est juste un projet de simulation, je ne peux pas garantir l'exactitude des chiffres et n'est pas responsable de choix que vous feriez dépendant de ce simulateur.

> Licence libre de choix

> Pour information, actuellement cet algo trouve la bonne mensualité en 28 essais en moyenne (100 max autorisés). Je pense que ca pourrait être largement amélioré et ne trouve pas de solution pour des taux d'intérêt annuel supérieur à 32% en général... mais bon, qui accepterai un taux à 32% d'interet ?

# TODO

- ajouter dans le tableau le capital remboursé cumulé
