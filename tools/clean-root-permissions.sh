#!/bin/bash
if [[ "$EUID" -ne 0 ]]; then
  cd "$(dirname "$0")"
  cd ..
  echo "find . -type f -name '*' | xargs chown ${USER}:${USER}" > temp.exec_as_sudo.sh
  echo "run subscript: this only works with sudo ... "
  echo "this will be executed:"
  echo "----------------------------------------"
  cat temp.exec_as_sudo.sh
  echo "----------------------------------------"
  sudo sh temp.exec_as_sudo.sh
  rm temp.exec_as_sudo.sh
else
  echo "NEVER RUN THIS AS SUDO ... "
  echo "if you did already somehow, run this script again without sudo"
fi

