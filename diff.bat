@echo off
cls
echo ================================================
echo generate commit message di dalam block dan bahasa inggris
echo ================================================
echo.

REM Simpan output ke file sementara
(
  echo generate commit message di dalam block dan bahasa inggris
  echo.
  echo [STATUS PERUBAHAN]
  git status --short
  echo.
  echo [GIT DIFF]
  git --no-pager diff
) > "%temp%\git_output.txt"

REM Tampilkan hasil di layar
type "%temp%\git_output.txt"

REM Salin hasil ke clipboard
type "%temp%\git_output.txt" | clip

echo.
echo ================================================
echo Output sudah disalin ke clipboard! 🎉
echo (tinggal paste di ChatGPT atau tempat lain)
echo ================================================
echo.
