<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Enums;

enum KeyCase: string {
    /** @example nick_name */
    case Snake = 'Snake';
    /** @example Nick_Name */
    case PascalSnake = 'PascalSnake';
    /** @example NICK_NAME */
    case Macro = 'Macro';
    /** @example nickName */
    case Camel = 'Camel';
    /** @example NickName */
    case Pascal = 'Pascal';
    /** @example nick-name */
    case Kebab = 'Kebab';
    /** @example nick-Name */
    case CamelKebab = 'CamelKebab';
    /** @example Nick-Name */
    case Train = 'Train';
}
