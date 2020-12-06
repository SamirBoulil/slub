<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symplify\ConsoleColorDiff\ConsoleColorDiffBundle::class => ['dev' => true, 'test' => true],
    Symplify\ComposerJsonManipulator\ComposerJsonManipulatorBundle::class => ['dev' => true, 'test' => true],
];
