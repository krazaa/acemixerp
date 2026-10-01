<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-light']) }}>
    {{ $slot }}
</button>
