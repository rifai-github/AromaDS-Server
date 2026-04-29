@props([
    'name' => 'unit',
    'value' => '',
    'required' => true,
    'class' => 'form-input',
    'placeholder' => 'Select Unit'
])

<select name="{{ $name }}" class="{{ $class }}" {{ $required ? 'required' : '' }}>
    <option value="">{{ $placeholder }}</option>
    <option value="pcs" {{ $value === 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
    <option value="kg" {{ $value === 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
    <option value="liter" {{ $value === 'liter' ? 'selected' : '' }}>Liter (ltr)</option>
    <option value="bottle" {{ $value === 'bottle' ? 'selected' : '' }}>Bottle</option>
    <option value="pack" {{ $value === 'pack' ? 'selected' : '' }}>Pack</option>
    <option value="cartridge" {{ $value === 'cartridge' ? 'selected' : '' }}>Cartridge</option>
    <option value="unit" {{ $value === 'unit' ? 'selected' : '' }}>Unit</option>
    <option value="box" {{ $value === 'box' ? 'selected' : '' }}>Box</option>
    <option value="set" {{ $value === 'set' ? 'selected' : '' }}>Set</option>
    <option value="roll" {{ $value === 'roll' ? 'selected' : '' }}>Roll</option>
    <option value="sheet" {{ $value === 'sheet' ? 'selected' : '' }}>Sheet</option>
    <option value="meter" {{ $value === 'meter' ? 'selected' : '' }}>Meter (m)</option>
    <option value="centimeter" {{ $value === 'centimeter' ? 'selected' : '' }}>Centimeter (cm)</option>
    <option value="gram" {{ $value === 'gram' ? 'selected' : '' }}>Gram (g)</option>
    <option value="milliliter" {{ $value === 'milliliter' ? 'selected' : '' }}>Milliliter (ml)</option>
</select>
