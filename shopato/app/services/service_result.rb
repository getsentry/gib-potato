class ServiceResult
  attr_reader :data, :errors, :message

  def initialize(success:, data: nil, errors: [], message: nil)
    @success = success
    @data = data
    @errors = Array(errors)
    @message = message
  end

  def success?
    @success
  end

  def failure?
    !@success
  end

  def error_messages
    @errors
  end

  def self.success(data: nil)
    new(success: true, data: data)
  end

  def self.failure(errors: [], message: nil)
    new(success: false, errors: errors, message: message)
  end
end
