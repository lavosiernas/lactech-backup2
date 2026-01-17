import { useRef } from 'react';

interface FilePickerProps {
  onFileSelect: (file: File) => void;
  accept?: string;
  multiple?: boolean;
  directory?: boolean;
}

export const useFilePicker = () => {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const directoryInputRef = useRef<HTMLInputElement>(null);

  const openFilePicker = (accept?: string, multiple = false) => {
    return new Promise<File[]>((resolve) => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = accept || '*/*';
      input.multiple = multiple;
      input.onchange = (e) => {
        const files = Array.from((e.target as HTMLInputElement).files || []);
        resolve(files);
      };
      input.click();
    });
  };

  const openDirectoryPicker = () => {
    return new Promise<FileList | null>((resolve) => {
      const input = document.createElement('input');
      input.type = 'file';
      input.webkitdirectory = true;
      input.onchange = (e) => {
        resolve((e.target as HTMLInputElement).files);
      };
      input.click();
    });
  };

  return { openFilePicker, openDirectoryPicker };
};




