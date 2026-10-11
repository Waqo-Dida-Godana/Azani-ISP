<?php

// Business rules for Azani (all amounts in KSh)
return [
    'registration_fee'   => 8500,
    'installation_fee'   => 10000,
    'pc_unit_cost'       => 40000,
    'upgrade_discount'   => 0.10,   // 10% off the plan being upgraded to
    'overdue_fine_rate'  => 0.15,   // 15% of the amount due
    'reconnection_fee'   => 1000,
    'disconnection_day'  => 10,     // day of the subsequent month
];
