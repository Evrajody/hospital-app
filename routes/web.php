<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

     $arbre = arbre_comptable(7);
    return view('welcome');
});
