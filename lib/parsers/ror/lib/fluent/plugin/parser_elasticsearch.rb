module Fluent
  class TextParser

    parser_elasticsearch = Proc.new {
      RegexpParser.new(
        /^\[(?<time>[^ ]* [^ ]*)\]\[(?<log_level>[^ ]*) *?\]\[(?<log_type>[^ ]*) *\] \[(?<node_name>[^\]]*) *\] (?<message>.+)/,
        {'time_format' => "%Y-%m-%d %H:%M:%S,%L"}
      )
    }
    TextParser.register_template('elasticsearch', parser_elasticsearch)

  end
end
