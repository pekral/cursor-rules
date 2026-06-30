<?php

declare(strict_types = 1);

use AgenticVibes\AgentSkills\InstallerPath;

test('resolveEditorFromComposerJson returns editor when configured', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'agent-skills' => [
                'editor' => 'claude',
            ],
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBe('claude');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when no editor configured', function (): void {
    $root = installerCreateProjectRoot();

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null for invalid editor value', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'agent-skills' => [
                'editor' => 'invalid',
            ],
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when composer.json does not exist', function (): void {
    $root = sys_get_temp_dir() . '/no-composer-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, recursive: true);

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson is case-insensitive for editor key', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'agent-skills' => [
                'Editor' => 'Claude',
            ],
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBe('claude');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when extra is not an array', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => 'not-an-array',
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when agent-skills config is not an array', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'agent-skills' => 'not-an-array',
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null for invalid JSON', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', 'not valid json');

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});
