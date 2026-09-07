<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Enums;

enum WebServiceResponseCode: int
{
    case InternalError = 2000;
    case InvalidPlan = 2001;
    case LineNotFound = 2002;
    case TooManyReceptors = 2003;
    case InvalidLine = 2004;
    case InvalidApiKey = 2005;
    case IpNotAllowed = 2006;
    case DuplicateLocalId = 2007;
    case UserInformationNotFound = 2008;
    case EmptyReceptors = 2009;
    case InvalidReceptors = 2010;
    case EmptyBody = 2011;
    case EmptyLine = 2012;
    case EmptyMessage = 2013;
    case InvalidReceptor = 2014;
    case EmptyReceptor = 2015;
    case MessageTooLarge = 2016;
    case InvalidLineSelector = 2017;
    case Unauthorized = 2018;
    case InvalidSendRange = 2019;
    case AllReceptorsBlacklisted = 2020;
    case MessageContainsForbiddenWords = 2021;
    case NotEnoughCredit = 2022;
    case DuplicateTag = 2023;
    case InvalidParameter = 2024;
    case ReceptorBlacklisted = 2025;
    case InvalidLinkInMessage = 2026;
    case TemplateNotApproved = 2027;
    case InvalidTemplateParameter = 2028;
    case InvalidLocalIds = 2029;
    case EmptyLocalIds = 2030;
    case EmptyMessageIds = 2031;
    case InvalidSmsType = 2032;
    case LineNotActive = 2033;
    case LineExpired = 2034;
    case MessageLimitReached = 2035;
    case RequestLimitReached = 2036;
    case InvalidSendTime = 2037;
    case InvalidExpiry = 2038;
    case InvalidTemplateId = 2039;
    case ProfileNotFound = 2040;
    case ProfileExpired = 2041;
    case FileNotFound = 2042;
    case InvalidFile = 2043;
    case AccessDenied = 2044;
    case Rejected = 2045;

    /**
     * The HTTP status the API returns alongside this response code (doc §3.4).
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::InternalError => 500,
            self::InvalidPlan => 400,
            self::LineNotFound => 404,
            self::TooManyReceptors => 400,
            self::InvalidLine => 400,
            self::InvalidApiKey => 401,
            self::IpNotAllowed => 403,
            self::DuplicateLocalId => 409,
            self::UserInformationNotFound => 404,
            self::EmptyReceptors => 400,
            self::InvalidReceptors => 400,
            self::EmptyBody => 400,
            self::EmptyLine => 400,
            self::EmptyMessage => 400,
            self::InvalidReceptor => 400,
            self::EmptyReceptor => 400,
            self::MessageTooLarge => 413,
            self::InvalidLineSelector => 400,
            self::Unauthorized => 401,
            self::InvalidSendRange => 400,
            self::AllReceptorsBlacklisted => 403,
            self::MessageContainsForbiddenWords => 400,
            self::NotEnoughCredit => 402,
            self::DuplicateTag => 409,
            self::InvalidParameter => 400,
            self::ReceptorBlacklisted => 403,
            self::InvalidLinkInMessage => 400,
            self::TemplateNotApproved => 400,
            self::InvalidTemplateParameter => 400,
            self::InvalidLocalIds => 400,
            self::EmptyLocalIds => 400,
            self::EmptyMessageIds => 400,
            self::InvalidSmsType => 400,
            self::LineNotActive => 400,
            self::LineExpired => 410,
            self::MessageLimitReached => 429,
            self::RequestLimitReached => 429,
            self::InvalidSendTime => 400,
            self::InvalidExpiry => 400,
            self::InvalidTemplateId => 400,
            self::ProfileNotFound => 400,
            self::ProfileExpired => 400,
            self::FileNotFound => 400,
            self::InvalidFile => 400,
            self::AccessDenied => 403,
            self::Rejected => 400,
        };
    }
}
