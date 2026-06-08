@props(['model' => ''])

<div
    x-data="{
        pwd: '',
        get lenOk() { return this.pwd.length >= 8 },
        get upperOk() { return /[A-Z]/.test(this.pwd) },
        get digitOk() { return /[0-9]/.test(this.pwd) },
        get specialOk() { return /[^A-Za-z0-9]/.test(this.pwd) },
        get score() {
            let s = 0;
            if (this.lenOk) s++;
            if (this.upperOk) s++;
            if (this.digitOk) s++;
            if (this.specialOk) s++;
            return s;
        },
        get barColor() {
            if (this.score <= 1) return '#dc2626';
            if (this.score === 2) return '#f59e0b';
            if (this.score === 3) return '#ca8a04';
            return '#16a34a';
        }
    }"
    {{ $attributes->merge(['class' => 'password-strength']) }}
>
    {{ $slot }}

    <div style="margin-top: 0.5rem;" aria-live="polite">
        <div style="display: flex; gap: 4px; margin-bottom: 6px;">
            <template x-for="i in 4" :key="i">
                <div
                    :style="'flex:1;height:4px;border-radius:2px;background:' + (score >= i ? barColor : '#e2e8f0')"
                ></div>
            </template>
        </div>
        <ul style="margin: 0; padding: 0; list-style: none; font-size: 12px; color: #64748b; display: grid; gap: 2px;">
            <li :style="lenOk ? 'color:#16a34a' : ''">Almeno 8 caratteri</li>
            <li :style="upperOk ? 'color:#16a34a' : ''">Una lettera maiuscola</li>
            <li :style="digitOk ? 'color:#16a34a' : ''">Un numero</li>
            <li :style="specialOk ? 'color:#16a34a' : ''">Un carattere speciale</li>
        </ul>
    </div>
</div>
