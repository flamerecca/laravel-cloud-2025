<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\Assignment;

class UploadAssignment extends Component
{
    use WithFileUploads;

    public $name = '';
    public $email = '';
    public $file = '';

    public $successMessage;

    protected array $rules = [
        'name' => 'required|string|max:50',
        'email' => 'required|email|max:100',
        'file' => 'required|file|mimetypes:application/pdf|max:512000', // 5MB
    ];

    public function submit(): void
    {
        $this->validate();

        $originalName = $this->file->getClientOriginalName();
        $filename = Str::uuid() . '.pdf';
        $path = $this->file->storeAs('assignments', $filename);

        Assignment::create([
            'name' => $this->name,
            'email' => $this->email,
            'file_path' => $path,
            'original_name' => $originalName,
        ]);

        // 清除表單欄位
        $this->reset(['name', 'email', 'file']);
        $this->successMessage = '上傳成功';
    }

    public function render(): View
    {
        return view('livewire.upload-assignment')
            ->layout('components.layouts.base');
    }
}
