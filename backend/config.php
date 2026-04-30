<?php
// backend/config.php

// Keep wallet values within a safe numeric range (avoid DB overflow / negative displays).
// Adjust these limits to match your database column precision if needed.
const WALLET_MAX_BALANCE = 99999999.99;
const WALLET_MAX_SINGLE_LOAD = 99999999.99;

