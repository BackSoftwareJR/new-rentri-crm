<?php

namespace App\Http\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class AdminPage extends Component
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function adminView(
        string $view,
        array $data = [],
        ?string $current = null,
        string $breadcrumb = 'Admin',
        ?string $title = null,
        string $activeNav = 'audit',
    ): View {
        $pageTitle = $title ?? $current ?? 'Admin';

        return view($view, $data)->layout('layouts.segreteria', [
            'active'     => $activeNav,
            'breadcrumb' => $breadcrumb,
            'current'    => $current,
            'title'      => $pageTitle,
            'role'       => 'Admin',
            'user'       => auth()->user()?->name ?? 'Utente',
        ]);
    }
}
