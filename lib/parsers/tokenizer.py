# -*- coding: utf-8 -*-

from peak.util.proxies import LazyProxy
from pyparsing import (
    Word, QuotedString, oneOf, CaselessLiteral, White,
    OneOrMore, Optional, alphanums, srange, ZeroOrMore, CaselessKeyword,
    nestedExpr, Forward, Group)
from .grammar_parsers import (
    parse_logical_expression, parse_compare_expression, parse_free_text,
    parse_paren_base_logical_expression, join_brackets, join_words,
    parse_facet_compare_expression, parse_one_or_more_facets_expression,
    parse_single_nested_expression, parse_base_nested_expression,
    parse_single_facet_expression, parse_base_facets_expression,
    parse_type_expression, parse_one_or_more_logical_expressions,
    parse_type_logical_facets_expression, parse_one_or_more_aggs_expression,
    parse_single_aggs_expression, parse_base_aggs_expression,
    parse_highlight_expression, parse_highlight_field_expression,
    parse_sort_field_expression, parse_sort_expression,
    parse_sort_field_option)

unicode_printables = u''.join(unichr(c) for c in xrange(65536)
                              if not unichr(c).isspace())


def get_word():
    return Word(unicode_printables, excludeChars=[')'])


def get_value():
    word = Word(unicode_printables, excludeChars=[')'])
    quoted_word = QuotedString('"', unquoteResults=False, escChar='\\')
    return quoted_word | word


def get_key():
    return Word(unicode_printables,
                excludeChars=[':', ':>', ':>=', ':<', ':<=', '('])


def get_operator():
    return oneOf(u": :< :> :<= :>= :=")


def get_logical_operator():
    return CaselessKeyword('AND') | CaselessKeyword('OR') | White().suppress()


def get_highlight_expression():
    field_expression = Word(srange("[a-zA-Z0-9_.*]"))
    field_expression.setParseAction(parse_highlight_field_expression)
    fields_expression = OneOrMore(
        field_expression + Optional(',').suppress())
    fields_expression.setParseAction(parse_highlight_expression)
    highlight_expression = Word('highlight:').suppress() \
        + Word('[').suppress() \
        + fields_expression + Word(']').suppress()
    return highlight_expression


def get_sort_expression():
    value_expression = Word(srange("[a-zA-Z0-9_.*]"))
    value_expression.setParseAction(lambda tokens: tokens[0])

    quoted_value_expression = Word('"').suppress() +\
        value_expression + Word('"').suppress()

    option_value = value_expression | quoted_value_expression
    option_value.setParseAction(lambda tokens: tokens[0])

    simple_option = Word(srange("[a-zA-Z0-9_.*]")) +\
        Word(':').suppress() + option_value

    simple_option.setParseAction(lambda tokens: (tokens[0], tokens[1]))

    option = Forward()
    option << (simple_option |
               (Word(srange("[a-zA-Z0-9_.*]")) +
                Word(':').suppress() +
                nestedExpr(content=option)))

    option.setParseAction(
        lambda tokens: parse_sort_field_option(tokens.asList())
    )

    exp = option + ZeroOrMore(Word(',').suppress() + option)

    field_expression = Optional('-') + Word(
        srange("[a-zA-Z0-9_.*]")
    ) + Optional(nestedExpr(content=exp))

    field_expression.setParseAction(parse_sort_field_expression)
    fields_expression = field_expression + ZeroOrMore(
        Word(',').suppress() + field_expression)
    fields_expression.setParseAction(parse_sort_expression)
    sort_expression = Word('sort:').suppress() \
        + Word('[').suppress() \
        + fields_expression + Word(']').suppress()
    return sort_expression


def get_logical_expression():
    logical_operator = get_logical_operator()
    compare_expression = get_key() + get_operator() + get_value()
    compare_expression.setParseAction(parse_compare_expression)
    base_logical_expression = (compare_expression
                               + logical_operator
                               + compare_expression).setParseAction(
        parse_logical_expression) | compare_expression | Word(
        unicode_printables).setParseAction(parse_free_text)
    logical_expression = ('(' + base_logical_expression + ')').setParseAction(
        parse_paren_base_logical_expression) | base_logical_expression
    return logical_expression


def get_nested_logical_expression():
    operator = get_operator()
    logical_operator = get_logical_operator()
    value = get_value()
    key = get_key()

    paren_value = '(' + OneOrMore(
        logical_operator | value).setParseAction(join_words) + ')'
    paren_value.setParseAction(join_brackets)
    facet_compare_expression = key + operator + paren_value | value
    facet_compare_expression.setParseAction(parse_facet_compare_expression)
    facet_base_logical_expression = (facet_compare_expression
                                     + Optional(logical_operator)).setParseAction(
                                         parse_logical_expression) | value
    facet_logical_expression = ('(' + facet_base_logical_expression
                                + ')').setParseAction(
        parse_paren_base_logical_expression) | facet_base_logical_expression
    return facet_logical_expression


def get_facet_expression():
    facet_logical_expression = get_nested_logical_expression()
    single_facet_expression = Word(
        srange("[a-zA-Z0-9_.]")) +\
        Optional(
            Word('(').suppress() +
            OneOrMore(facet_logical_expression).setParseAction(
                parse_one_or_more_facets_expression) +
            Word(')').suppress())
    single_facet_expression.setParseAction(parse_single_facet_expression)
    base_facets_expression = OneOrMore(single_facet_expression
                                       + Optional(',').suppress())
    base_facets_expression.setParseAction(parse_base_facets_expression)
    facets_expression = Word('facets:').suppress() \
        + Word('[').suppress() \
        + base_facets_expression + Word(']').suppress()
    return facets_expression


def get_aggregations_expression():
    aggs_logical_expression = get_nested_logical_expression()
    single_aggs_expression = Word(
        srange("[a-zA-Z0-9_.]")) +\
        Optional(
            Word('(').suppress() +
            OneOrMore(aggs_logical_expression).setParseAction(
                parse_one_or_more_aggs_expression) +
            Word(')').suppress())
    single_aggs_expression.setParseAction(parse_single_aggs_expression)
    base_aggs_expression = OneOrMore(single_aggs_expression
                                       + Optional(',').suppress())
    base_aggs_expression.setParseAction(parse_base_aggs_expression)
    aggs_expression = Word('aggregations:').suppress() \
        + Word('[').suppress() \
        + base_aggs_expression + Word(']').suppress()
    return aggs_expression


def get_nested_expression():
    facet_logical_expression = get_nested_logical_expression()
    single_nested_expression = Word(
        srange("[a-zA-Z0-9_.]")) +\
        Optional(
            Word('(').suppress() +
            OneOrMore(facet_logical_expression).setParseAction(
                parse_one_or_more_facets_expression) +
            Word(')').suppress())
    single_nested_expression.setParseAction(parse_single_nested_expression)
    base_nested_expression = OneOrMore(single_nested_expression
                                       + Optional(',').suppress())
    base_nested_expression.setParseAction(parse_base_nested_expression)
    nested_expression = Word('nested:').suppress()\
        + Word('[').suppress()\
        + base_nested_expression\
        + Word(']').suppress()
    return nested_expression


def _construct_grammar():
    logical_operator = get_logical_operator()
    logical_expression = get_logical_expression()

    facets_expression = get_facet_expression()
    highlight_expression = get_highlight_expression()
    sort_expression = get_sort_expression()
    aggs_expression = get_aggregations_expression()
    nested_expression = get_nested_expression()

    # The below line describes how the type expression should be.
    type_expression = Word('type')\
        + Word(':').suppress()\
        + Word(srange("[a-zA-Z0-9_]"))\
        + Optional(CaselessLiteral('AND')).suppress()
    type_expression.setParseAction(parse_type_expression)

    base_expression = Optional(highlight_expression)\
        + Optional(sort_expression)\
        + Optional(type_expression)\
        + ZeroOrMore(
            (facets_expression
             | aggs_expression
             | nested_expression
             | logical_expression)
            + Optional(logical_operator)
        ).setParseAction(parse_one_or_more_logical_expressions)
    base_expression.setParseAction(parse_type_logical_facets_expression)

    return base_expression


def _sanitize_query(query_string):
    for char in [u'\n', u'\xa0', u'\t']:
        query_string = query_string.replace(char, u' ')
    return query_string.strip()

grammar = LazyProxy(_construct_grammar)


def tokenize(query_string):
    return grammar.parseString(_sanitize_query(query_string),
                               parseAll=True).asList()[0]
