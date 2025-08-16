<?php

namespace App;

enum PaymentMethodEnum: string
{
    case CASH = 'cash';

    case BASAK_KART = 'basak_kart';

    case IMECE_KART = 'imece_kart';

    case KREDI_KARTI = 'kredi_kartı';

    case CEK = 'cek';
}
