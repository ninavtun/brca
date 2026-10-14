<?php

function animalCode($id)
{
    return sprintf('BRCA-%04d', $id);
}


function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
