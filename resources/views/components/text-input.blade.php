@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#A85F3B] focus:ring-[#A85F3B] rounded-md shadow-sm']) }}>
