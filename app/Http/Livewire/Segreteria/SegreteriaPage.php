<?php

namespace App\Http\Livewire\Segreteria;

use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class SegreteriaPage extends Component
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function segreteriaView(
        string $view,
        array $data = [],
        ?string $active = null,
        ?string $current = null,
        string $breadcrumb = 'Home',
        ?string $title = null,
    ): View {
        $pageTitle = $title ?? $current ?? 'Segreteria';

        return view($view, $data)->layout('layouts.segreteria', [
            'active' => $active,
            'breadcrumb' => $breadcrumb,
            'current' => $current,
            'title' => $pageTitle,
            'role' => 'Segreteria',
            'user' => auth()->user()?->name ?? 'Utente',
        ]);
    }
}
