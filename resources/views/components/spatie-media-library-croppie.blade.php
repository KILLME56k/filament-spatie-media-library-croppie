<x-dynamic-component
    :component="$getFieldWrapperView()"
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isAvatar() || $isLabelHidden()"
    :helper-text="$getHelperText()"
    :hint="$getHint()"
    :hint-action="$getHintAction()"
    :hint-color="$getHintColor()"
    :hint-icon="$getHintIcon()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
>

    @php
        $imageCropAspectRatio = $getImageCropAspectRatio();
        $imageResizeTargetHeight = $getImageResizeTargetHeight();
        $imageResizeTargetWidth = $getImageResizeTargetWidth();
        $imageResizeMode = $getImageResizeMode();
        $shouldTransformImage = $imageCropAspectRatio || $imageResizeTargetHeight || $imageResizeTargetWidth;
        $imageFormat = $getImageFormat();
        $imageQuality = $getImageQuality();
    @endphp


    <div class="relative" x-data="{
        fileHasUploaded : false,
        fileHasDeleted: false,
     }"
    >

        <div
            x-data="fileUploadFormComponent({
            acceptedFileTypes: {{ json_encode($getAcceptedFileTypes(), JSON_THROW_ON_ERROR) }},
            canDownload: {{ $canDownload() ? 'true' : 'false' }},
            canOpen: {{ $canOpen() ? 'true' : 'false' }},
            canPreview: {{ $canPreview() ? 'true' : 'false' }},
            canReorder: {{ $canReorder() ? 'true' : 'false' }},
            deleteUploadedFileUsing: async (fileKey) => {
                fileHasDeleted = true;
                fileHasUploaded = false;
                return await $wire.deleteUploadedFile('{{ $getStatePath() }}', fileKey)
            },
            getUploadedFileUrlsUsing: async () => {
                return await $wire.getUploadedFileUrls('{{ $getStatePath() }}')
            },
            imageCropAspectRatio: {{ $imageCropAspectRatio ? "'{$imageCropAspectRatio}'" : 'null' }},
            imagePreviewHeight: {{ ($height = $getImagePreviewHeight()) ? "'{$height}'" : 'null' }},
            imageResizeMode: {{ $imageResizeMode ? "'{$imageResizeMode}'" : 'null' }},
            imageResizeTargetHeight: {{ $imageResizeTargetHeight ? "'{$imageResizeTargetHeight}'" : 'null' }},
            imageResizeTargetWidth: {{ $imageResizeTargetWidth ? "'{$imageResizeTargetWidth}'" : 'null' }},
            isAvatar: {{ $isAvatar() ? 'true' : 'false' }},
            loadingIndicatorPosition: '{{ $getLoadingIndicatorPosition() }}',
            locale: @js(app()->getLocale()),
            panelAspectRatio: {{ ($aspectRatio = $getPanelAspectRatio()) ? "'{$aspectRatio}'" : 'null' }},
            panelLayout: {{ ($layout = $getPanelLayout()) ? "'{$layout}'" : 'null' }},
            placeholder: @js($getPlaceholder()),
            maxSize: {{ ($size = $getMaxSize()) ? "'{$size} KB'" : 'null' }},
            minSize: {{ ($size = $getMinSize()) ? "'{$size} KB'" : 'null' }},
            removeUploadedFileUsing: async (fileKey) => {
                fileHasDeleted = true;
                fileHasUploaded = false;
                return await $wire.removeUploadedFile('{{ $getStatePath() }}', fileKey)
            },
            removeUploadedFileButtonPosition: '{{ $getRemoveUploadedFileButtonPosition() }}',
            reorderUploadedFilesUsing: async (files) => {
                return await $wire.reorderUploadedFiles('{{ $getStatePath() }}', files)
            },
            shouldAppendFiles: {{ $shouldAppendFiles() ? 'true' : 'false' }},
            shouldTransformImage: {{ $shouldTransformImage ? 'true' : 'false' }},
            state: $wire.{{ $applyStateBindingModifiers('entangle(\'' . $getStatePath() . '\')') }},
            uploadButtonPosition: '{{ $getUploadButtonPosition() }}',
            uploadProgressIndicatorPosition: '{{ $getUploadProgressIndicatorPosition() }}',
            uploadUsing: (fileKey, file, success, error, progress) => {
                $wire.upload(`{{ $getStatePath() }}.${fileKey}`, file, () => {
                    fileHasUploaded = true;
                    fileHasDeleted = false;
                    success(fileKey)
                }, error, progress)
            },
        })"
            wire:ignore
            {!! ($id = $getId()) ? "id=\"{$id}\"" : null !!}
            style="min-height: {{ $isAvatar() ? '8em' : ($getPanelLayout() === 'compact' ? '2.625em' : '4.75em') }}"
            {{ $attributes->merge($getExtraAttributes())->class([
                'filament-forms-file-upload-component',
                'w-32 mx-auto' => $isAvatar(),
            ]) }}
            {{ $getExtraAlpineAttributeBag() }}
        >
            <input
                x-ref="input"
                {{ $isDisabled() ? 'disabled' : '' }}
                {{ $isMultiple() ? 'multiple' : '' }}
                type="file"
                {{ $getExtraInputAttributeBag() }}
                dusk="filament.forms.{{ $getStatePath() }}"

            />
        </div>

        @php
            $uniquemodalevent = \Illuminate\Support\Str::of($getStatePath())->replace('.','')->replace('_','');
        @endphp

        <input
            {{ $isDisabled() ? 'disabled' : '' }}
            type="file"
            accept="{{\Illuminate\Support\Arr::join($getAcceptedFileTypes(),',','')}}"

            x-show = "(({{$getState() == null  ? 'true':'false'}} && !fileHasUploaded) || fileHasDeleted) || {{$isMultiple()?'true':'false'}}"

            @class([
                    'croppie-image-picker',
                    "left-0 w-full cursor-pointer" => !$isAvatar(),
                    "avatar  w-32  cursor-pointer" => $isAvatar(),
            ])

            type="file"
            x-on:change = "function(){
                var fileType = event.target.files[0]['type'];
                if (!(fileType.search(`image`) >= 0)) {
                    new Notification()
                    .title('{{ __('filament-spatie-media-library-croppie::error') }}')
                        .danger()
                        .body('{{ __('filament-spatie-media-library-croppie::invalid') }}')
                        .send()
                        return;
                }
                $dispatch('on-croppie-modal-show-{{$uniquemodalevent}}', {
                    id: 'croppie-modal-{{ $getStatePath() }}',
                    files: event.target.files,

                })
            }" />


    </div>

    <div x-data="{files:null,}" @on-croppie-modal-show-{{ $uniquemodalevent }}.window="
            files = $event.detail.files;
            id = $event.detail.id;
            $dispatch('open-modal', {id: id})"
            class="h-0"
    >
        <x-filament::modal
            class=""
            width="{{$getModalSize()}}"

            id="croppie-modal-{{ $getStatePath() }}"
        >
            @if ($hasModalHeading())
            <x-slot name="heading">
                <x-filament::modal.heading>
                    {{$getModalHeading()}}
                </x-filament::modal.heading>
            </x-slot>
            @endif

            <div class=" z-5 w-full h-full flex flex-col justify-between"
                 x-data="imageCropper({
                        imageUrl: '',
                        shape: `{{$isAvatar()?'circle':'square'}}`,
                        files: files,
                        viewportWidth: `{{$getViewportWidth()}}`,
                        viewportHeight: `{{$getViewportHeight()}}`,
                        boundaryWidth: `{{$getBoundaryWidth()}}`,
                        boundaryHeight: `{{$getBoundaryHeight()}}`,
                        statePath: `{{$getStatePath()}}`,
                        showZoomer: `{{$getShowZoomer()}}`,
                        format: `{{$imageFormat}}`,
                        quality: `{{$imageQuality}}`
                    })" x-cloak
            >
                <div class="h-full w-full" wire:ignore >
                    {{-- init Alpine --}}
                    <div class="h-full w-full relative"  >
                        <div  x-on:click.prevent class="bg-transparent h-full">
                            <div class="m-auto flex-col" x-ref="croppie"></div>
                            <div class="flex justify-center gap-2 pb-2">
                                @if ($isLeftRotationEnabled())
                                    <x-filament::button class="px-2" type="button"  x-on:click.prevent="rotateLeft()">
                                        <svg class="filament-button-icon w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
                                        </svg>
                                    </x-filament::button>
                                @endif

                                @if ($isRightRotationEnabled())
                                <x-filament::button type="button"  x-on:click.prevent="rotateRight()" >
                                    <svg style="transform: scale(-1,1)" class="filament-button-icon w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
                                    </svg>
                                </x-filament::button>
                                @endif
                            </div>
                        </div>

                        <div x-show="!showCroppie" class="absolute top-0 left-0 w-full h-full bg-white z-10 flex items-center justify-center">
                            <div aria-label="{{ __('filament-spatie-media-library-croppie::loading') }}" role="status" class="flex items-center space-x-2">
                                <span class="text-xs font-medium text-gray-500">{{ __('filament-spatie-media-library-croppie::loading') }}</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center items-center gap-2">
                    <x-filament::button type="button"  x-on:click.prevent="saveCroppie()">
                        @lang('filament::resources/pages/edit-record.form.actions.save.label')
                    </x-filament::button>
                </div>
            </div>
        </x-filament::modal>
    </div>

</x-dynamic-component>
